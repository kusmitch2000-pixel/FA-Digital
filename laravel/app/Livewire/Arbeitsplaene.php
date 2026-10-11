<?php

namespace App\Livewire;

use App\Actions\Fertigung\SollzeitUebernehmen;
use App\Livewire\Concerns\BrauchtRecht;
use App\Models\ArbeitsplanPosition;
use App\Models\Arbeitsplatz;
use App\Models\Artikel;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Artikel und Arbeitspläne mit Prüfmerkmalen. Hier übernimmt die Arbeitsvorbereitung Abweichungen
 * aus abgeschlossenen Aufträgen in die Kalkulation (Rückmeldung vom 01.10.2026).
 */
class Arbeitsplaene extends Component
{
    use BrauchtRecht;

    protected string $recht = 'arbeitsplaene';

    /** Gewählter Artikel, z. B. /artikel?artikel=W-200 */
    #[Url]
    public string $artikel = '';

    public string $artikelnummer = '';

    public string $bezeichnung = '';

    public ?int $agNummer = null;

    public string $agBezeichnung = '';

    public ?int $arbeitsplatzId = null;

    public ?float $ruestzeit = 0;

    public ?float $stueckzeit = null;

    public function mount(): void
    {
        if ($this->artikel === '') {
            $this->artikel = Artikel::orderBy('artikelnummer')->value('artikelnummer') ?? '';
        }

        $this->arbeitsplatzId = Arbeitsplatz::where('aktiv', true)->orderBy('nummer')->value('id');
        $this->naechsteAgNummer();
    }

    public function auswaehlen(string $artikelnummer): void
    {
        $this->artikel = $artikelnummer;
        $this->resetValidation();
        $this->naechsteAgNummer();
    }

    public function uebernehmen(int $positionId, string $feld): void
    {
        $this->resetErrorBag();
        $aenderung = app(SollzeitUebernehmen::class)(auth()->user(), $positionId, $feld);

        session()->flash('status', ($feld === 'ruestzeit' ? 'Rüstzeit' : 'Stückzeit').' von '.zahl($aenderung->alter_wert, 2)
            .' auf '.zahl($aenderung->neuer_wert, 2).' min geändert. Neue Aufträge rechnen mit dem neuen Wert.');
    }

    public function artikelAnlegen(): void
    {
        $this->resetErrorBag();
        $daten = $this->validate([
            'artikelnummer' => ['required', 'string', 'max:20', 'unique:artikel,artikelnummer'],
            'bezeichnung' => ['required', 'string', 'max:100'],
        ]);

        Artikel::create($daten);
        $this->auswaehlen($daten['artikelnummer']);
        $this->reset('artikelnummer', 'bezeichnung');
    }

    public function positionHinzufuegen(): void
    {
        $this->resetErrorBag();
        $artikel = Artikel::where('artikelnummer', $this->artikel)->firstOrFail();

        $daten = $this->validate([
            'agNummer' => ['required', 'integer', 'min:1', 'max:9999', Rule::unique('arbeitsplan_position', 'ag_nummer')->where('artikel_id', $artikel->id)],
            'agBezeichnung' => ['required', 'string', 'max:80'],
            'arbeitsplatzId' => ['required', Rule::exists('arbeitsplatz', 'id')->where('aktiv', 1)],
            'ruestzeit' => ['required', 'numeric', 'min:0', 'max:99999'],
            'stueckzeit' => ['required', 'numeric', 'min:0', 'max:99999'],
        ]);

        $artikel->positionen()->create([
            'ag_nummer' => $daten['agNummer'],
            'bezeichnung' => $daten['agBezeichnung'],
            'arbeitsplatz_id' => $daten['arbeitsplatzId'],
            'ruestzeit_min' => $daten['ruestzeit'],
            'stueckzeit_min' => $daten['stueckzeit'],
        ]);

        $this->reset('agBezeichnung', 'stueckzeit');
        $this->ruestzeit = 0;
        $this->naechsteAgNummer();
    }

    public function positionEntfernen(int $positionId): void
    {
        $this->resetErrorBag();
        $position = ArbeitsplanPosition::whereHas('artikel', fn ($a) => $a->where('artikelnummer', $this->artikel))->findOrFail($positionId);

        if (DB::table('arbeitsgang')->where('arbeitsplan_position_id', $position->id)->exists()) {
            $this->addError('aktion', "AG {$position->ag_nummer} steckt schon in Aufträgen und bleibt deshalb im Arbeitsplan.");

            return;
        }

        $position->delete();
        $this->naechsteAgNummer();
    }

    private function naechsteAgNummer(): void
    {
        $hoechste = ArbeitsplanPosition::whereHas('artikel', fn ($a) => $a->where('artikelnummer', $this->artikel))->max('ag_nummer');
        $this->agNummer = ((int) ($hoechste / 10) + 1) * 10;
    }

    public function render(): View
    {
        $vorschlaege = DB::table('v_sollzeit_vorschlag')->get();

        return view('livewire.arbeitsplaene', [
            'liste' => Artikel::with('positionen')->orderBy('artikelnummer')->get(),
            'gewaehlt' => Artikel::with(['positionen.arbeitsplatz', 'positionen.pruefmerkmale.zertifikatsart', 'positionen.aenderungen.geaendertVon'])
                ->where('artikelnummer', $this->artikel)->first(),
            'vorschlaege' => $vorschlaege->where('artikelnummer', $this->artikel)->sortBy('ag_nummer'),
            'offeneVorschlaege' => $vorschlaege
                ->filter(fn ($v) => $v->vorschlag_ruestzeit_min !== null || $v->vorschlag_stueckzeit_min !== null)
                ->countBy('artikelnummer'),
            'plaetze' => Arbeitsplatz::where('aktiv', true)->orderBy('nummer')->get(),
        ])->layout('layouts::production', ['title' => 'Artikel & Arbeitspläne']);
    }
}
