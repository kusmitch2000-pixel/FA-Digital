<?php

namespace App\Livewire;

use App\Livewire\Concerns\BrauchtRecht;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Auftragsübersicht für alle Rollen (Anwendungsfall 8), gelesen aus der Sicht v_auftragsuebersicht.
 * Links die Suche nach einem bestimmten Teil, daneben der Status. Die Spalte „Aktueller Prozess“
 * zeigt, an welchem Arbeitsplatz jedes Teil gerade ist.
 */
class Auftraege extends Component
{
    use BrauchtRecht;

    /** Statuswerte, die als „offen“ gelten: Der Auftrag ist weder fertig noch storniert */
    private const OFFEN = ['angelegt', 'freigegeben', 'in_arbeit', 'messen'];

    protected string $recht = 'auftraege';

    #[Url]
    public string $suche = '';

    /** leer = alle, „offen“ = alle offenen, sonst ein Status */
    #[Url]
    public string $status = '';

    public function zuruecksetzen(): void
    {
        $this->reset('suche', 'status');
    }

    public function render(): View
    {
        $suche = '%'.addcslashes(trim($this->suche), '%_\\').'%';

        $auftraege = DB::table('v_auftragsuebersicht')
            ->when(trim($this->suche) !== '', fn (Builder $abfrage) => $abfrage->where(fn (Builder $oder) => $oder
                ->where('auftragsnummer', 'like', $suche)
                ->orWhere('artikelnummer', 'like', $suche)
                ->orWhere('artikel', 'like', $suche)
                ->orWhere('kunde', 'like', $suche)))
            ->when($this->status === 'offen', fn (Builder $abfrage) => $abfrage->whereIn('status', self::OFFEN))
            ->when($this->status !== '' && $this->status !== 'offen', fn (Builder $abfrage) => $abfrage->where('status', $this->status))
            ->orderByRaw("FIELD(status, 'messen', 'in_arbeit', 'freigegeben', 'angelegt', 'fertig', 'storniert')")
            ->orderBy('liefertermin')
            ->get();

        // Aktueller Prozess: der erste noch nicht erledigte Arbeitsgang je Auftrag mit seinem Arbeitsplatz
        $aktuell = DB::table('arbeitsgang as ag')
            ->join('arbeitsplatz as ap', 'ap.id', '=', 'ag.arbeitsplatz_id')
            ->whereIn('ag.fertigungsauftrag_id', $auftraege->pluck('fertigungsauftrag_id'))
            ->where('ag.status', '<>', 'erledigt')
            ->orderBy('ag.ag_nummer')
            ->get(['ag.fertigungsauftrag_id', 'ag.ag_nummer', 'ag.bezeichnung', 'ag.status', 'ap.bezeichnung as arbeitsplatz'])
            ->unique('fertigungsauftrag_id')
            ->keyBy('fertigungsauftrag_id');

        // Anzahl je Status über alle Aufträge, für die Status-Knöpfe
        $anzahl = DB::table('v_auftragsuebersicht')->selectRaw('status, COUNT(*) AS anzahl')->groupBy('status')->pluck('anzahl', 'status');

        return view('livewire.auftraege', [
            'auftraege' => $auftraege,
            'aktuell' => $aktuell,
            'anzahl' => $anzahl,
            'gesamt' => (int) $anzahl->sum(),
            'offen' => (int) $anzahl->only(self::OFFEN)->sum(),
            'gefiltert' => trim($this->suche) !== '' || $this->status !== '',
        ])->layout('layouts::production', ['title' => 'Aufträge']);
    }
}
