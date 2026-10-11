<?php

namespace App\Livewire;

use App\Actions\Fertigung\Statuswechsel;
use App\Livewire\Concerns\BrauchtRecht;
use App\Models\AuftragZertifikat;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Werkszertifikate erstellen und freigeben (Anwendungsfall 9). Die Daten stammen aus den Checklisten
 * der Werker. Freigeben kann der Meister erst, wenn der Auftrag fertig ist und alles in Ordnung ist.
 */
class Werkszertifikate extends Component
{
    use BrauchtRecht;

    protected string $recht = 'zertifikate';

    public function freigeben(int $zertifikatId): void
    {
        $this->resetErrorBag();
        $this->pruefeRecht('zertifikat.freigeben');

        $zertifikat = AuftragZertifikat::findOrFail($zertifikatId);
        app(Statuswechsel::class)->zertifikatFreigeben(auth()->user(), $zertifikat);

        session()->flash('status', 'Werkszertifikat '.$zertifikat->fresh()->dateiname.' ist freigegeben.');
    }

    public function render(): View
    {
        return view('livewire.werkszertifikate', [
            'zertifikate' => DB::table('v_zertifikat_status')
                ->orderByRaw("FIELD(status, 'gefordert', 'erstellt', 'freigegeben')")
                ->orderByDesc('bereit_zur_freigabe')
                ->orderBy('auftragsnummer')
                ->get(),
            'darfFreigeben' => $this->darf('zertifikat.freigeben'),
        ])->layout('layouts::production', ['title' => 'Werkszertifikate']);
    }
}
