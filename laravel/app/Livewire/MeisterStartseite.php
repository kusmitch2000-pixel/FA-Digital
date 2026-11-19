<?php

namespace App\Livewire;

use App\Livewire\Concerns\BrauchtRecht;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MeisterStartseite extends Component
{
    use BrauchtRecht;

    protected string $recht = 'meister.startseite';

    public function render(): View
    {
        return view('livewire.meister-startseite', [
            // Die Datenbanksicht zählt nur offene Aufträge mit einem Termin vor heute als verspätet.
            'ueberfaelligeAuftraege' => DB::table('v_auftragsuebersicht')
                ->where('verzug', true)->orderBy('liefertermin')->orderBy('auftragsnummer')->get(),
            // Dieselbe Bedingung wie bei der Zertifikatsfreigabe; bereits freigegebene Zertifikate ausnehmen.
            'freigabebereiteZertifikate' => DB::table('v_zertifikat_status')
                ->where('bereit_zur_freigabe', true)->where('status', '!=', 'freigegeben')
                ->orderBy('auftragsnummer')->orderBy('kuerzel')->get(),
        ])->layout('layouts::production', ['title' => 'Startseite']);
    }
}
