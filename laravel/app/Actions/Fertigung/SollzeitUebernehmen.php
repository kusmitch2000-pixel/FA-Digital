<?php

namespace App\Actions\Fertigung;

use App\Models\ArbeitsplanAenderung;
use App\Models\ArbeitsplanPosition;
use App\Models\Benutzer;
use Illuminate\Support\Facades\DB;

/**
 * Übernahme von Abweichungen in die neue Kalkulation (Rückmeldung vom 01.10.2026).
 * Die Sicht v_sollzeit_vorschlag liefert einen Vorschlag, sobald die Ist-Zeiten abgeschlossener Aufträge
 * mehr als 10 % von der Soll-Zeit abweichen. Laufende Aufträge behalten ihre Soll-Zeit,
 * neue Aufträge rechnen mit dem neuen Wert. Alter und neuer Wert landen in arbeitsplan_aenderung.
 */
class SollzeitUebernehmen
{
    public function __invoke(Benutzer $von, int $positionId, string $feld): ArbeitsplanAenderung
    {
        if (! in_array($feld, ['ruestzeit', 'stueckzeit'], true)) {
            Fachfehler::melden('Unbekanntes Feld.');
        }

        $vorschlag = DB::table('v_sollzeit_vorschlag')->where('arbeitsplan_position_id', $positionId)->first();
        $neuerWert = $feld === 'ruestzeit' ? $vorschlag?->vorschlag_ruestzeit_min : $vorschlag?->vorschlag_stueckzeit_min;

        if ($neuerWert === null) {
            Fachfehler::melden('Für diesen Arbeitsgang gibt es keinen Vorschlag mehr.');
        }

        return DB::transaction(function () use ($von, $positionId, $feld, $vorschlag, $neuerWert) {
            $position = ArbeitsplanPosition::lockForUpdate()->findOrFail($positionId);
            $spalte = $feld === 'ruestzeit' ? 'ruestzeit_min' : 'stueckzeit_min';

            $aenderung = ArbeitsplanAenderung::create([
                'arbeitsplan_position_id' => $position->id,
                'feld' => $feld,
                'alter_wert' => $position->{$spalte},
                'neuer_wert' => $neuerWert,
                'begruendung' => 'Ø Ist-'.($feld === 'ruestzeit' ? 'Rüstzeit' : 'Stückzeit').' aus '.$vorschlag->anzahl_auftraege
                    .' abgeschlossenen '.($vorschlag->anzahl_auftraege == 1 ? 'Auftrag' : 'Aufträgen'),
                'geaendert_von' => $von->id,
                'geaendert_am' => now(),
            ]);

            $position->update([$spalte => $neuerWert]);

            return $aenderung;
        });
    }
}
