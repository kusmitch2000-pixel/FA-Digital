<?php

namespace App\Livewire;

use App\Actions\Fertigung\Werkstatt;
use App\Livewire\Concerns\BrauchtRecht;
use App\Models\Arbeitsgang;
use App\Models\Arbeitsplatz;
use App\Models\Unterbrechung;
use App\Models\Unterbrechungsgrund;
use App\Models\Zeitbuchung;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Werker-Terminal (Anwendungsfälle 1 bis 5): Arbeitsvorrat am Arbeitsplatz, Rüsten und Bearbeiten stempeln,
 * mit Grund unterbrechen, Checkliste abarbeiten und den Arbeitsgang mit Gutmenge und Ausschuss fertig melden.
 * Die Abläufe selbst stehen in App\Actions\Fertigung\Werkstatt.
 */
class Terminal extends Component
{
    use BrauchtRecht;

    protected string $recht = 'terminal';

    /** Gewählter Arbeitsplatz, steht in der Adresse, z. B. /werker?arbeitsplatz=AP-20 */
    #[Url(as: 'arbeitsplatz')]
    public string $arbeitsplatz = '';

    public ?int $grund = null;

    public string $unterbrechungBemerkung = '';

    public ?int $gutmenge = null;

    public ?int $ausschuss = 0;

    public string $bemerkung = '';

    public string $notiz = '';

    /** @var array<int, string> Messwerte der Checkliste je Prüfpunkt-ID */
    public array $istwert = [];

    /** @var array<int, string> Bemerkungen der Checkliste je Prüfpunkt-ID */
    public array $pruefBemerkung = [];

    public function mount(): void
    {
        $aktuell = $this->werkstatt()->aktuellerArbeitsgang(auth()->user());

        if ($this->arbeitsplatz === '') {
            $this->arbeitsplatz = $aktuell?->arbeitsplatz->nummer
                ?? Arbeitsplatz::where('aktiv', true)->orderBy('nummer')->value('nummer')
                ?? '';
        }

        $this->gutmenge = $aktuell?->auftrag->menge;
    }

    public function starten(int $arbeitsgangId, string $taetigkeit): void
    {
        $this->resetErrorBag();
        $this->werkstatt()->starten(auth()->user(), $arbeitsgangId, $taetigkeit);

        $aktuell = $this->werkstatt()->aktuellerArbeitsgang(auth()->user());
        $this->arbeitsplatz = $aktuell->arbeitsplatz->nummer;
        $this->gutmenge = $aktuell->auftrag->menge;
        $this->ausschuss = 0;
    }

    public function wechseln(string $taetigkeit): void
    {
        $this->resetErrorBag();
        $this->werkstatt()->taetigkeitWechseln(auth()->user(), $taetigkeit);
    }

    public function unterbrechen(): void
    {
        $this->resetErrorBag();
        $this->werkstatt()->unterbrechen(auth()->user(), (int) $this->grund, $this->unterbrechungBemerkung);
        $this->reset('grund', 'unterbrechungBemerkung');
    }

    public function fortsetzen(): void
    {
        $this->resetErrorBag();
        $this->werkstatt()->fortsetzen(auth()->user());
    }

    public function pruefen(int $pruefpunktId, ?string $ergebnis = null): void
    {
        $this->resetErrorBag();
        $this->werkstatt()->pruefen(
            auth()->user(),
            $pruefpunktId,
            $this->istwert[$pruefpunktId] ?? null,
            $ergebnis,
            $this->pruefBemerkung[$pruefpunktId] ?? null,
        );
    }

    public function notizSpeichern(): void
    {
        $this->resetErrorBag();
        $this->validate(['notiz' => ['required', 'string', 'max:1000']]);
        $aktuell = $this->werkstatt()->aktuellerArbeitsgang(auth()->user());
        if (! $aktuell) {
            $this->addError('notiz', 'Bitte zuerst einen Arbeitsgang starten.');

            return;
        }
        DB::table('arbeitsgang_notiz')->insert([
            'arbeitsgang_id' => $aktuell->id, 'benutzer_id' => auth()->id(),
            'text' => trim($this->notiz), 'erstellt_am' => now(),
        ]);
        $this->reset('notiz');
        session()->flash('status', 'Notiz gespeichert.');
    }

    public function fertigMelden(): void
    {
        $this->resetErrorBag();
        $aktuell = $this->werkstatt()->aktuellerArbeitsgang(auth()->user());
        $this->werkstatt()->fertigMelden(auth()->user(), (int) $this->gutmenge, (int) $this->ausschuss, $this->bemerkung);
        if ($aktuell?->fresh()->status === 'messen') {
            $this->redirectRoute('terminal.messung', ['arbeitsgang' => $aktuell->id], navigate: false);

            return;
        }
        $this->reset('gutmenge', 'ausschuss', 'bemerkung', 'istwert', 'pruefBemerkung');
        session()->flash('status', 'Arbeitsgang fertig gemeldet.');
    }

    private function werkstatt(): Werkstatt
    {
        return app(Werkstatt::class);
    }

    public function render(): View
    {
        $benutzer = auth()->user();
        $aktuell = $this->werkstatt()->aktuellerArbeitsgang($benutzer);
        $aktuell?->load('pruefpunkte');

        return view('livewire.terminal', [
            'benutzer' => $benutzer,
            'messungen' => Arbeitsgang::with('auftrag')->where('status', 'messen')->where('fertig_gemeldet_von', $benutzer->id)->get(),
            'mitZertifikat' => $aktuell ? $aktuell->auftrag->zertifikate()->exists() : false,
            'aktuell' => $aktuell,
            'zeit' => $aktuell ? DB::table('v_arbeitsgang_zeit')->where('arbeitsgang_id', $aktuell->id)->first() : null,
            'buchung' => $aktuell ? Zeitbuchung::where('arbeitsgang_id', $aktuell->id)->where('benutzer_id', $benutzer->id)->whereNull('ende')->where('gueltig', true)->first() : null,
            'unterbrechung' => $aktuell ? Unterbrechung::with('grund')->where('arbeitsgang_id', $aktuell->id)->where('benutzer_id', $benutzer->id)->whereNull('ende')->first() : null,
            'plaetze' => Arbeitsplatz::where('aktiv', true)->orderBy('nummer')->get(),
            'notizen' => $aktuell ? DB::table('arbeitsgang_notiz')->join('benutzer', 'benutzer.id', '=', 'arbeitsgang_notiz.benutzer_id')->where('arbeitsgang_id', $aktuell->id)->select('arbeitsgang_notiz.*', 'benutzer.vorname', 'benutzer.nachname')->orderByDesc('erstellt_am')->get() : collect(),
            'vorrat' => DB::table('v_arbeitsvorrat')
                ->when($benutzer->rolle === 'werker', fn ($query) => $query->whereExists(fn ($sub) => $sub->selectRaw('1')->from('arbeitsgang_zuordnung')->whereColumn('arbeitsgang_zuordnung.arbeitsgang_id', 'v_arbeitsvorrat.arbeitsgang_id')->where('arbeitsgang_zuordnung.benutzer_id', $benutzer->id)))
                ->when($benutzer->rolle !== 'werker', fn ($query) => $query->where('arbeitsplatz_nummer', $this->arbeitsplatz))
                ->orderByRaw("FIELD(bereitschaft, 'laeuft', 'unterbrochen', 'bereit', 'wartet')")
                ->orderBy('liefertermin')
                ->orderBy('auftragsnummer')
                ->get(),
            'gruende' => Unterbrechungsgrund::where('aktiv', true)->orderBy('id')->get(),
        ])->layout('layouts::production', ['title' => 'Werker-Terminal']);
    }
}
