<?php

namespace App\Livewire;

use App\Livewire\Concerns\BrauchtRecht;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Auftragsübersicht für alle Rollen (Anwendungsfall 8), gelesen aus der Sicht v_auftragsuebersicht */
class Auftraege extends Component
{
    use BrauchtRecht;

    protected string $recht = 'auftraege';

    #[Url]
    public string $suche = '';

    #[Url]
    public string $status = '';

    public function render(): View
    {
        $suche = '%'.addcslashes(trim($this->suche), '%_\\').'%';

        $auftraege = DB::table('v_auftragsuebersicht')
            ->when(trim($this->suche) !== '', fn ($abfrage) => $abfrage->where(fn ($oder) => $oder
                ->where('auftragsnummer', 'like', $suche)
                ->orWhere('artikelnummer', 'like', $suche)
                ->orWhere('artikel', 'like', $suche)
                ->orWhere('kunde', 'like', $suche)))
            ->when($this->status !== '', fn ($abfrage) => $abfrage->where('status', $this->status))
            ->orderByRaw("FIELD(status, 'in_arbeit', 'freigegeben', 'angelegt', 'fertig', 'storniert')")
            ->orderBy('liefertermin')
            ->get();

        return view('livewire.auftraege', ['auftraege' => $auftraege])
            ->layout('layouts::production', ['title' => 'Aufträge']);
    }
}
