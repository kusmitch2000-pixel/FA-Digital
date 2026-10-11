<?php

namespace App\Actions\Fertigung;

use App\Models\AuftragZertifikat;
use App\Models\Benutzer;
use App\Models\Fertigungsauftrag;
use Illuminate\Support\Facades\DB;

/**
 * Statuswechsel, die eine Person bewusst auslöst:
 * angelegt → freigegeben (Arbeitsvorbereitung), angelegt/freigegeben → storniert (nur vor dem ersten Start),
 * fertig → nachkalkuliert (Controlling) und die Freigabe eines Werkszertifikats (Meister).
 * „in Arbeit“ und „fertig“ setzt das Werker-Terminal automatisch.
 */
class Statuswechsel
{
    public function freigeben(Benutzer $von, Fertigungsauftrag $auftrag): void
    {
        if ($auftrag->status !== 'angelegt') {
            Fachfehler::melden('Nur angelegte Aufträge lassen sich freigeben.');
        }

        $auftrag->update(['status' => 'freigegeben', 'freigegeben_von' => $von->id, 'freigegeben_am' => now()]);
    }

    public function stornieren(Fertigungsauftrag $auftrag): void
    {
        $begonnen = DB::table('zeitbuchung')
            ->join('arbeitsgang', 'arbeitsgang.id', '=', 'zeitbuchung.arbeitsgang_id')
            ->where('arbeitsgang.fertigungsauftrag_id', $auftrag->id)
            ->exists();

        if (! in_array($auftrag->status, ['angelegt', 'freigegeben'], true) || $begonnen) {
            Fachfehler::melden('Stornieren geht nur, solange noch niemand an dem Auftrag gearbeitet hat.');
        }

        $auftrag->update(['status' => 'storniert']);
    }

    public function nachkalkulationAbschliessen(Fertigungsauftrag $auftrag): void
    {
        if ($auftrag->status !== 'fertig') {
            Fachfehler::melden('Nur fertige Aufträge lassen sich nachkalkulieren.');
        }

        $auftrag->update(['status' => 'nachkalkuliert', 'nachkalkuliert_am' => now()]);
    }

    /** Freigabe erst, wenn der Auftrag fertig ist und alle Prüfpunkte in Ordnung sind (Sicht v_zertifikat_status) */
    public function zertifikatFreigeben(Benutzer $von, AuftragZertifikat $zertifikat): void
    {
        $stand = DB::table('v_zertifikat_status')->where('auftrag_zertifikat_id', $zertifikat->id)->first();

        if ($zertifikat->status === 'freigegeben') {
            Fachfehler::melden('Das Zertifikat ist schon freigegeben.');
        }

        if (! $stand?->bereit_zur_freigabe) {
            Fachfehler::melden('Freigabe erst möglich, wenn der Auftrag fertig ist und alle Prüfpunkte in Ordnung sind.');
        }

        $jetzt = now();

        $zertifikat->update([
            'status' => 'freigegeben',
            'erstellt_am' => $zertifikat->erstellt_am ?? $jetzt,
            'freigegeben_von' => $von->id,
            'freigegeben_am' => $jetzt,
            'dateiname' => 'WZ_'.$stand->auftragsnummer.'_'.$stand->kuerzel.'.pdf',
        ]);
    }
}
