<?php

namespace App\Livewire;

use App\Actions\Fertigung\Buchungskorrektur;
use App\Actions\Fertigung\Statuswechsel;
use App\Livewire\Concerns\BrauchtRecht;
use App\Models\Benutzer;
use App\Models\Fertigungsauftrag;
use App\Models\Unterbrechung;
use App\Models\Zeitbuchung;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Auftragsdetail: Kopfdaten, Arbeitsgänge mit Soll und Ist, Checkliste, Zertifikate und alle Buchungen.
 * Die Arbeitsvorbereitung gibt hier frei oder storniert. Der Meister trägt Buchungen nach
 * oder korrigiert sie (Anwendungsfall 6).
 */
class AuftragDetail extends Component
{
    use BrauchtRecht;

    protected string $recht = 'auftraege';

    public Fertigungsauftrag $auftrag;

    /** Formular „Buchung nachtragen oder korrigieren“ */
    public ?int $korrekturVon = null;

    public ?int $arbeitsgangId = null;

    public ?int $werkerId = null;

    public string $taetigkeit = 'bearbeiten';

    public string $beginn = '';

    public string $ende = '';

    public string $begruendung = '';

    public function mount(Fertigungsauftrag $auftrag): void
    {
        $this->auftrag = $auftrag;
        $this->nachtragBeginnen();
    }

    public function freigeben(): void
    {
        $this->resetErrorBag();
        $this->pruefeRecht('auftrag.freigeben');
        app(Statuswechsel::class)->freigeben(auth()->user(), $this->auftrag);
        session()->flash('status', "Auftrag {$this->auftrag->auftragsnummer} ist freigegeben und erscheint im Arbeitsvorrat.");
    }

    public function stornieren(): void
    {
        $this->resetErrorBag();
        $this->pruefeRecht('auftrag.freigeben');
        app(Statuswechsel::class)->stornieren($this->auftrag);
        session()->flash('status', "Auftrag {$this->auftrag->auftragsnummer} ist storniert.");
    }

    /** Formular mit den Werten einer Buchung füllen, die korrigiert werden soll */
    public function korrekturBeginnen(int $buchungId): void
    {
        $this->pruefeRecht('buchung.korrigieren');

        $buchung = Zeitbuchung::whereIn('arbeitsgang_id', $this->auftrag->arbeitsgaenge()->pluck('id'))->findOrFail($buchungId);

        $this->resetValidation();
        $this->korrekturVon = $buchung->id;
        $this->arbeitsgangId = $buchung->arbeitsgang_id;
        $this->werkerId = $buchung->benutzer_id;
        $this->taetigkeit = $buchung->taetigkeit;
        $this->beginn = $buchung->beginn->format('Y-m-d\TH:i');
        $this->ende = $buchung->ende?->format('Y-m-d\TH:i') ?? '';
        $this->begruendung = '';
    }

    public function nachtragBeginnen(): void
    {
        $this->resetValidation();
        $this->korrekturVon = null;
        $this->arbeitsgangId = $this->auftrag->arbeitsgaenge()->value('id');
        $this->werkerId = null;
        $this->taetigkeit = 'bearbeiten';
        $this->beginn = now()->subHour()->format('Y-m-d\TH:i');
        $this->ende = now()->format('Y-m-d\TH:i');
        $this->begruendung = '';
    }

    public function buchungSpeichern(): void
    {
        $this->resetErrorBag();
        $this->pruefeRecht('buchung.korrigieren');
        $korrektur = app(Buchungskorrektur::class);

        if ($this->korrekturVon) {
            $korrektur->korrigieren(auth()->user(), $this->korrekturVon, $this->taetigkeit, $this->beginn, $this->ende, $this->begruendung);
            session()->flash('status', 'Buchung korrigiert. Die alte Buchung bleibt als ungültig sichtbar.');
        } else {
            if (! $this->auftrag->arbeitsgaenge()->whereKey($this->arbeitsgangId)->exists()) {
                $this->addError('arbeitsgangId', 'Bitte einen Arbeitsgang wählen.');

                return;
            }

            $korrektur->nachtragen(auth()->user(), (int) $this->arbeitsgangId, (int) $this->werkerId, $this->taetigkeit, $this->beginn, $this->ende, $this->begruendung);
            session()->flash('status', 'Buchung nachgetragen.');
        }

        $this->nachtragBeginnen();
    }

    public function render(): View
    {
        $this->auftrag->refresh()->load(['artikel', 'angelegtVon', 'zertifikate.zertifikatsart', 'arbeitsgaenge.arbeitsplatz']);
        $arbeitsgangIds = $this->auftrag->arbeitsgaenge->pluck('id');

        return view('livewire.auftrag-detail', [
            'zeiten' => DB::table('v_nachkalkulation_arbeitsgang')->where('fertigungsauftrag_id', $this->auftrag->id)->get()->keyBy('arbeitsgang_id'),
            'checkliste' => DB::table('v_checkliste')->where('auftragsnummer', $this->auftrag->auftragsnummer)->orderBy('ag_nummer')->orderBy('reihenfolge')->get(),
            'buchungen' => Zeitbuchung::with(['benutzer', 'erfasstVon', 'arbeitsgang'])->whereIn('arbeitsgang_id', $arbeitsgangIds)->orderByDesc('beginn')->orderByDesc('id')->get(),
            'unterbrechungen' => Unterbrechung::with(['grund', 'benutzer'])->whereIn('arbeitsgang_id', $arbeitsgangIds)->orderByDesc('beginn')->get(),
            'werker' => Benutzer::whereIn('rolle', ['werker', 'meister'])->where('aktiv', true)->orderBy('nachname')->get(),
            'darfKorrigieren' => $this->darf('buchung.korrigieren') && ! in_array($this->auftrag->status, ['nachkalkuliert', 'storniert'], true),
            'darfFreigeben' => $this->darf('auftrag.freigeben'),
        ])->layout('layouts::production', ['title' => $this->auftrag->auftragsnummer]);
    }
}
