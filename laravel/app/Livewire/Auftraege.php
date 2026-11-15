<?php

namespace App\Livewire;

use App\Livewire\Concerns\BrauchtRecht;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Auftragsübersicht für alle Rollen (Anwendungsfall 8), gelesen aus der Sicht v_auftragsuebersicht */
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

    #[Url]
    public bool $verzug = false;

    public function zuruecksetzen(): void
    {
        $this->reset('suche', 'status', 'verzug');
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
            ->when($this->verzug, fn (Builder $abfrage) => $abfrage->where('verzug', 1))
            ->orderByRaw("FIELD(status, 'messen', 'in_arbeit', 'freigegeben', 'angelegt', 'fertig', 'storniert')")
            ->orderBy('liefertermin')
            ->get();

        // Anzahl je Status über alle Aufträge, für das Auswahlfeld
        $anzahl = DB::table('v_auftragsuebersicht')->selectRaw('status, COUNT(*) AS anzahl')->groupBy('status')->pluck('anzahl', 'status');

        return view('livewire.auftraege', [
            'auftraege' => $auftraege,
            'anzahl' => $anzahl,
            'gesamt' => (int) $anzahl->sum(),
            'offen' => (int) $anzahl->only(self::OFFEN)->sum(),
            'gefiltert' => trim($this->suche) !== '' || $this->status !== '' || $this->verzug,
        ])->layout('layouts::production', ['title' => 'Aufträge']);
    }
}
