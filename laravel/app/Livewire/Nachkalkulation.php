<?php

namespace App\Livewire;

use App\Actions\Fertigung\Statuswechsel;
use App\Livewire\Concerns\BrauchtRecht;
use App\Models\Fertigungsauftrag;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Nachkalkulation (Anwendungsfall 10): Soll-Ist-Vergleich je Arbeitsgang und für den ganzen Auftrag.
 * Alle Zahlen kommen aus den Sichten v_nachkalkulation_arbeitsgang und v_nachkalkulation_auftrag.
 */
class Nachkalkulation extends Component
{
    use BrauchtRecht;

    protected string $recht = 'nachkalkulation';

    #[Url]
    public string $auftrag = '';

    public function mount(): void
    {
        if ($this->auftrag === '') {
            $this->auftrag = $this->auswahl()->first()->auftragsnummer ?? '';
        }
    }

    public function abschliessen(): void
    {
        $this->resetErrorBag();
        $this->pruefeRecht('nachkalkulation.abschliessen');

        $auftrag = Fertigungsauftrag::where('auftragsnummer', $this->auftrag)->firstOrFail();
        app(Statuswechsel::class)->nachkalkulationAbschliessen($auftrag);

        session()->flash('status', "Nachkalkulation für {$auftrag->auftragsnummer} ist abgeschlossen.");
    }

    /** @return Collection<int, \stdClass> fertige und nachkalkulierte Aufträge aus v_nachkalkulation_auftrag */
    private function auswahl(): Collection
    {
        return DB::table('v_nachkalkulation_auftrag')
            ->whereIn('status', ['fertig', 'nachkalkuliert'])
            ->orderByRaw("FIELD(status, 'fertig', 'nachkalkuliert')")
            ->orderByDesc('abgeschlossen_am')
            ->get();
    }

    public function render(): View
    {
        $auswahl = $this->auswahl();
        $kopf = $auswahl->firstWhere('auftragsnummer', $this->auftrag);

        return view('livewire.nachkalkulation', [
            'auswahl' => $auswahl,
            'kopf' => $kopf,
            'zeilen' => $kopf
                ? DB::table('v_nachkalkulation_arbeitsgang')->where('fertigungsauftrag_id', $kopf->fertigungsauftrag_id)->orderBy('ag_nummer')->get()
                : collect(),
            'vorschlaege' => $kopf
                ? DB::table('v_sollzeit_vorschlag')->where('artikelnummer', $kopf->artikelnummer)
                    ->where(fn ($q) => $q->whereNotNull('vorschlag_ruestzeit_min')->orWhereNotNull('vorschlag_stueckzeit_min'))->count()
                : 0,
            'darfAbschliessen' => $this->darf('nachkalkulation.abschliessen'),
        ])->layout('layouts::production', ['title' => 'Nachkalkulation']);
    }
}
