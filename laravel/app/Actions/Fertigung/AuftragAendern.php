<?php

namespace App\Actions\Fertigung;

use App\Models\Fertigungsauftrag;
use App\Support\Zertifikate;
use Illuminate\Support\Facades\DB;

/**
 * Auftrag ändern, solange noch niemand daran gearbeitet hat (Status angelegt oder freigegeben,
 * keine Zeitbuchung). Menge, Liefertermin, Kunde, Bemerkung und die geforderten Zertifikate lassen
 * sich ändern. Die Soll-Zeiten rechnen die Sichten mit der neuen Menge, die Checkliste wird an die
 * Zertifikate angepasst: Prüfpunkte weggefallener Zertifikate entfallen, neue kommen dazu.
 */
class AuftragAendern
{
    /**
     * @param  array{menge: int, liefertermin: string, kunde: ?string, bemerkung: ?string}  $daten
     * @param  list<int>  $zertifikatsarten  IDs aus zertifikatsart
     */
    public function __invoke(Fertigungsauftrag $auftrag, array $daten, array $zertifikatsarten): void
    {
        self::pruefeAenderbar($auftrag);

        $erlaubt = Zertifikate::fuerArtikel($auftrag->artikel_id)->pluck('id')->all();
        $zertifikatsarten = array_values(array_unique($zertifikatsarten));
        if (array_diff($zertifikatsarten, $erlaubt) !== []) {
            Fachfehler::melden('Mindestens ein Zertifikat gilt nicht für diesen Artikel.', 'zertifikate');
        }

        DB::transaction(function () use ($auftrag, $daten, $zertifikatsarten) {
            $auftrag->update([
                'menge' => $daten['menge'],
                'liefertermin' => $daten['liefertermin'],
                'kunde' => $daten['kunde'] ?: null,
                'bemerkung' => $daten['bemerkung'] ?: null,
            ]);

            // Zertifikate abgleichen. Noch nichts ist gestartet, also sind alle nur „gefordert“.
            $auftrag->zertifikate()->whereNotIn('zertifikatsart_id', $zertifikatsarten)->delete();
            $vorhanden = $auftrag->zertifikate()->pluck('zertifikatsart_id')->all();
            foreach (array_diff($zertifikatsarten, $vorhanden) as $zertifikatsartId) {
                $auftrag->zertifikate()->create(['zertifikatsart_id' => $zertifikatsartId]);
            }

            // Checkliste: Prüfpunkte, die nur zu einem Zertifikat gehören, neu aufbauen
            DB::table('pruefpunkt')
                ->join('arbeitsgang', 'arbeitsgang.id', '=', 'pruefpunkt.arbeitsgang_id')
                ->join('pruefmerkmal', 'pruefmerkmal.id', '=', 'pruefpunkt.pruefmerkmal_id')
                ->where('arbeitsgang.fertigungsauftrag_id', $auftrag->id)
                ->whereNotNull('pruefmerkmal.zertifikatsart_id')
                ->delete();

            DB::insert('
                INSERT INTO pruefpunkt (arbeitsgang_id, pruefmerkmal_id, reihenfolge, bezeichnung, art, sollwert, untere_grenze, obere_grenze, einheit)
                SELECT ag.id, pm.id, pm.reihenfolge, pm.bezeichnung, pm.art, pm.sollwert, pm.untere_grenze, pm.obere_grenze, pm.einheit
                FROM arbeitsgang ag
                JOIN pruefmerkmal pm ON pm.arbeitsplan_position_id = ag.arbeitsplan_position_id
                WHERE ag.fertigungsauftrag_id = ?
                  AND pm.zertifikatsart_id IN (SELECT zertifikatsart_id FROM auftrag_zertifikat WHERE fertigungsauftrag_id = ?)',
                [$auftrag->id, $auftrag->id]);
        });
    }

    /** Ändern geht nur, solange noch niemand an dem Auftrag gearbeitet hat */
    public static function istAenderbar(Fertigungsauftrag $auftrag): bool
    {
        return in_array($auftrag->status, ['angelegt', 'freigegeben'], true)
            && ! DB::table('zeitbuchung')
                ->join('arbeitsgang', 'arbeitsgang.id', '=', 'zeitbuchung.arbeitsgang_id')
                ->where('arbeitsgang.fertigungsauftrag_id', $auftrag->id)
                ->exists();
    }

    public static function pruefeAenderbar(Fertigungsauftrag $auftrag): void
    {
        if (! self::istAenderbar($auftrag)) {
            Fachfehler::melden('Ändern geht nur, solange noch niemand an dem Auftrag gearbeitet hat.');
        }
    }
}
