<?php

namespace App\Livewire;

use App\Actions\Fertigung\AuftragAnlegen as AuftragAnlegenAktion;
use App\Livewire\Concerns\BrauchtRecht;
use App\Models\Artikel;
use App\Support\Zertifikate;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Auftrag anlegen (Rückmeldung vom 01.10.2026: Auftragserstellung bleibt). Schon hier wählt die
 * Arbeitsvorbereitung die geforderten Werkszertifikate. Daraus ergeben sich die Prüfpunkte für den Werker.
 */
class AuftragAnlegen extends Component
{
    use BrauchtRecht;

    protected string $recht = 'auftrag.anlegen';

    public ?int $artikelId = null;

    public ?int $menge = 1;

    public string $liefertermin = '';

    public string $kunde = '';

    public string $bemerkung = '';

    /** @var array<int, string> IDs der geforderten Zertifikatsarten (Werte der Checkboxen) */
    public array $zertifikate = [];

    public function mount(): void
    {
        $this->artikelId = Artikel::where('aktiv', true)->orderBy('artikelnummer')->value('id');
        $this->liefertermin = now()->addWeeks(2)->toDateString();
    }

    /** Anderer Artikel: Zertifikate abwählen, die für ihn nicht gelten */
    public function updatedArtikelId(): void
    {
        $erlaubt = Zertifikate::fuerArtikel($this->artikelId)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->zertifikate = array_values(array_intersect(array_map('strval', $this->zertifikate), $erlaubt));
    }

    public function speichern(bool $freigeben = false): void
    {
        $this->resetErrorBag();
        $daten = $this->validate([
            'artikelId' => ['required', 'integer', Rule::exists('artikel', 'id')->where('aktiv', 1)],
            'menge' => ['required', 'integer', 'min:1', 'max:100000'],
            'liefertermin' => ['required', 'date', 'after_or_equal:today'],
            'kunde' => ['nullable', 'string', 'max:100'],
            'bemerkung' => ['nullable', 'string', 'max:255'],
            'zertifikate' => ['array'],
            'zertifikate.*' => ['integer', 'exists:zertifikatsart,id'],
        ]);

        $erlaubt = Zertifikate::fuerArtikel((int) $daten['artikelId'])->pluck('id')->all();
        if (array_diff(array_map('intval', $daten['zertifikate']), $erlaubt) !== []) {
            $this->addError('zertifikate', 'Mindestens ein Zertifikat gilt nicht für diesen Artikel.');

            return;
        }

        $auftrag = app(AuftragAnlegenAktion::class)(auth()->user(), [
            'artikel_id' => $daten['artikelId'],
            'menge' => $daten['menge'],
            'liefertermin' => $daten['liefertermin'],
            'kunde' => $daten['kunde'],
            'bemerkung' => $daten['bemerkung'],
        ], array_values(array_map('intval', $daten['zertifikate'])), $freigeben);

        session()->flash('status', "Auftrag {$auftrag->auftragsnummer} ist ".($freigeben ? 'angelegt und freigegeben.' : 'angelegt. Zum Starten noch freigeben.'));

        $this->redirectRoute('auftrag', $auftrag);
    }

    public function render(): View
    {
        $artikel = Artikel::with('positionen.arbeitsplatz', 'positionen.pruefmerkmale.zertifikatsart')
            ->where('aktiv', true)->orderBy('artikelnummer')->get();
        $gewaehlt = $artikel->firstWhere('id', (int) $this->artikelId);
        $zertifikate = array_map('intval', $this->zertifikate);

        // Vorschau: Soll-Zeit = Rüstzeit + Menge × Stückzeit, Kosten mit dem aktuellen Stundensatz
        $vorschau = collect($gewaehlt->positionen ?? [])->map(function ($position) use ($zertifikate) {
            $sollZeit = (float) $position->ruestzeit_min + max(0, (int) $this->menge) * (float) $position->stueckzeit_min;
            $pruefpunkte = $position->pruefmerkmale->filter(fn ($merkmal) => $merkmal->zertifikatsart_id === null || in_array($merkmal->zertifikatsart_id, $zertifikate, true));

            return (object) [
                'position' => $position,
                'soll_zeit' => $sollZeit,
                'soll_kosten' => round($sollZeit / 60 * (float) $position->arbeitsplatz->stundensatz, 2),
                'pruefpunkte' => $pruefpunkte,
            ];
        });

        return view('livewire.auftrag-anlegen', [
            'artikel' => $artikel,
            'vorschau' => $vorschau,
            'zertifikatsarten' => Zertifikate::fuerArtikel($this->artikelId),
        ])->layout('layouts::production', ['title' => 'Auftrag anlegen']);
    }
}
