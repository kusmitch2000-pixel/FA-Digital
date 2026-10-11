<?php

namespace App\Http\Controllers;

use App\Models\AuftragZertifikat;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Druckansicht des Werkszeugnisses mit den Prüfergebnissen aus der Checkliste (Anwendungsfall 9) */
class WerkszeugnisController extends Controller
{
    public function __invoke(AuftragZertifikat $zertifikat): View
    {
        $zertifikat->load(['auftrag.artikel', 'zertifikatsart', 'freigegebenVon']);

        return view('werkszeugnis', [
            'zertifikat' => $zertifikat,
            'stand' => DB::table('v_zertifikat_status')->where('auftrag_zertifikat_id', $zertifikat->id)->first(),
            'pruefpunkte' => DB::table('v_checkliste')
                ->where('auftragsnummer', $zertifikat->auftrag->auftragsnummer)
                ->orderBy('ag_nummer')->orderBy('reihenfolge')
                ->get(),
        ]);
    }
}
