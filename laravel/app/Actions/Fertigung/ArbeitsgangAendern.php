<?php

namespace App\Actions\Fertigung;

use App\Models\ArbeitsplanAenderung;
use App\Models\ArbeitsplanPosition;
use App\Models\Benutzer;
use Illuminate\Support\Facades\DB;

/**
 * Die Arbeitsvorbereitung ändert Rüst- oder Stückzeit eines Arbeitsgangs im Arbeitsplan von Hand.
 * Wie bei der Übernahme eines Vorschlags landet jede geänderte Zeit mit altem und neuem Wert und
 * der Begründung in arbeitsplan_aenderung. Bestehende Aufträge behalten ihre kopierte Soll-Zeit,
 * nur neue Aufträge rechnen mit dem neuen Wert.
 */
class ArbeitsgangAendern
{
    /**
     * @return list<ArbeitsplanAenderung> eine Änderung je geänderter Zeit
     */
    public function __invoke(Benutzer $von, int $positionId, float $ruestzeit, float $stueckzeit, string $begruendung): array
    {
        $begruendung = trim($begruendung);

        if ($begruendung === '') {
            Fachfehler::melden('Bitte begründen, warum sich die Soll-Zeit ändert.', 'aenderungBegruendung');
        }

        return DB::transaction(function () use ($von, $positionId, $ruestzeit, $stueckzeit, $begruendung) {
            $position = ArbeitsplanPosition::lockForUpdate()->findOrFail($positionId);
            $neu = ['ruestzeit' => round($ruestzeit, 2), 'stueckzeit' => round($stueckzeit, 2)];
            $aenderungen = [];
            $werte = [];

            foreach ($neu as $feld => $wert) {
                $spalte = $feld.'_min';
                $alt = (float) $position->{$spalte};

                if (abs($alt - $wert) < 0.005) {
                    continue;
                }

                $aenderungen[] = ArbeitsplanAenderung::create([
                    'arbeitsplan_position_id' => $position->id,
                    'feld' => $feld,
                    'alter_wert' => $alt,
                    'neuer_wert' => $wert,
                    'begruendung' => $begruendung,
                    'geaendert_von' => $von->id,
                    'geaendert_am' => now(),
                ]);
                $werte[$spalte] = $wert;
            }

            if ($aenderungen === []) {
                Fachfehler::melden('Rüst- und Stückzeit sind unverändert. Es wurde nichts gespeichert.');
            }

            $position->update($werte);

            return $aenderungen;
        });
    }
}
