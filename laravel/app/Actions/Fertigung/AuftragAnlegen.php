<?php

namespace App\Actions\Fertigung;

use App\Models\ArbeitsplanPosition;
use App\Models\Benutzer;
use App\Models\Fertigungsauftrag;
use Illuminate\Support\Facades\DB;

/**
 * Fertigungsauftrag anlegen, mit den Werkszertifikaten, die der Kunde fordert.
 * Arbeitsgänge und Checklisten entstehen als Kopie aus Arbeitsplan und Prüfmerkmalen,
 * genau wie Ablauf 1 in datenbank/05_beispielablaeufe.sql.
 */
class AuftragAnlegen
{
    /**
     * @param  array{artikel_id: int, menge: int, liefertermin: string, kunde: ?string, bemerkung: ?string}  $daten
     * @param  list<int>  $zertifikatsarten  IDs aus zertifikatsart
     */
    public function __invoke(Benutzer $von, array $daten, array $zertifikatsarten = [], bool $freigeben = false): Fertigungsauftrag
    {
        if (! ArbeitsplanPosition::where('artikel_id', $daten['artikel_id'])->exists()) {
            Fachfehler::melden('Für diesen Artikel gibt es noch keinen Arbeitsplan.', 'artikelId');
        }

        return DB::transaction(function () use ($von, $daten, $zertifikatsarten, $freigeben) {
            $jetzt = now();

            $auftrag = Fertigungsauftrag::create([
                'auftragsnummer' => $this->naechsteNummer(),
                'artikel_id' => $daten['artikel_id'],
                'menge' => $daten['menge'],
                'kunde' => $daten['kunde'] ?: null,
                'liefertermin' => $daten['liefertermin'],
                'bemerkung' => $daten['bemerkung'] ?: null,
                'status' => $freigeben ? 'freigegeben' : 'angelegt',
                'angelegt_von' => $von->id,
                'freigegeben_von' => $freigeben ? $von->id : null,
                'freigegeben_am' => $freigeben ? $jetzt : null,
            ]);

            foreach (array_unique($zertifikatsarten) as $zertifikatsartId) {
                $auftrag->zertifikate()->create(['zertifikatsart_id' => $zertifikatsartId]);
            }

            // Arbeitsgänge: Soll-Zeiten und Stundensatz werden kopiert und bleiben so für diesen Auftrag fest
            DB::insert('
                INSERT INTO arbeitsgang (fertigungsauftrag_id, arbeitsplan_position_id, ag_nummer, bezeichnung, arbeitsplatz_id,
                                         soll_ruestzeit_min, soll_stueckzeit_min, stundensatz)
                SELECT ?, pos.id, pos.ag_nummer, pos.bezeichnung, pos.arbeitsplatz_id, pos.ruestzeit_min, pos.stueckzeit_min, ap.stundensatz
                FROM arbeitsplan_position pos
                JOIN arbeitsplatz ap ON ap.id = pos.arbeitsplatz_id
                WHERE pos.artikel_id = ?', [$auftrag->id, $auftrag->artikel_id]);

            // Checklisten: alle Prüfmerkmale ohne Zertifikatsart plus die für die geforderten Zertifikate
            DB::insert('
                INSERT INTO pruefpunkt (arbeitsgang_id, pruefmerkmal_id, reihenfolge, bezeichnung, art, sollwert, untere_grenze, obere_grenze, einheit)
                SELECT ag.id, pm.id, pm.reihenfolge, pm.bezeichnung, pm.art, pm.sollwert, pm.untere_grenze, pm.obere_grenze, pm.einheit
                FROM arbeitsgang ag
                JOIN pruefmerkmal pm ON pm.arbeitsplan_position_id = ag.arbeitsplan_position_id
                WHERE ag.fertigungsauftrag_id = ?
                  AND (pm.zertifikatsart_id IS NULL
                       OR pm.zertifikatsart_id IN (SELECT zertifikatsart_id FROM auftrag_zertifikat WHERE fertigungsauftrag_id = ?))',
                [$auftrag->id, $auftrag->id]);

            return $auftrag;
        });
    }

    /** Nächste freie Nummer, z. B. FA-1007 */
    private function naechsteNummer(): string
    {
        $hoechste = DB::table('fertigungsauftrag')
            ->lockForUpdate()
            ->selectRaw('MAX(CAST(SUBSTRING(auftragsnummer, 4) AS UNSIGNED)) AS nummer')
            ->value('nummer');

        return sprintf('FA-%04d', ((int) $hoechste) + 1);
    }
}
