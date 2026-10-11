<?php

namespace App\Livewire;

use App\Livewire\Concerns\BrauchtRecht;
use App\Support\Kennzahlen;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Auswertung (Anwendungsfall 11): abgeschlossene Aufträge, Zeitabweichung je Arbeitsplatz
 * und Unterbrechungen nach Grund. Jede Tabelle lässt sich als CSV exportieren.
 */
class Auswertung extends Component
{
    use BrauchtRecht;

    protected string $recht = 'auswertung';

    public function render(): View
    {
        return view('livewire.auswertung', [
            'auftraege' => Kennzahlen::auftraege(),
            'arbeitsplaetze' => Kennzahlen::arbeitsplaetze(),
            'unterbrechungen' => Kennzahlen::unterbrechungen(),
        ])->layout('layouts::production', ['title' => 'Auswertung']);
    }
}
