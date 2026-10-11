<?php

namespace App\Actions\Fertigung;

use App\Models\Arbeitsgang;
use App\Models\Benutzer;
use App\Models\Zeitbuchung;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Buchungen nachtragen und korrigieren (Anwendungsfall 6, Meister).
 * Nichts wird gelöscht: Eine Korrektur legt eine neue Buchung an und setzt die alte auf ungültig.
 * Eine Begründung ist Pflicht, die Datenbank prüft das zusätzlich (ck_zeitbuchung_begruendung).
 */
class Buchungskorrektur
{
    public function korrigieren(Benutzer $meister, int $buchungId, string $taetigkeit, string $beginn, string $ende, string $begruendung): Zeitbuchung
    {
        [$von, $bis, $begruendung] = $this->pruefeEingaben($taetigkeit, $beginn, $ende, $begruendung);

        return DB::transaction(function () use ($meister, $buchungId, $taetigkeit, $von, $bis, $begruendung) {
            $alt = Zeitbuchung::with('arbeitsgang.auftrag')->lockForUpdate()->findOrFail($buchungId);

            if (! $alt->gueltig) {
                Fachfehler::melden('Diese Buchung wurde schon korrigiert.');
            }

            if ($alt->ende === null) {
                Fachfehler::melden('Eine laufende Buchung lässt sich erst nach dem Ende korrigieren.');
            }

            $this->pruefeAuftrag($alt->arbeitsgang);

            $neu = Zeitbuchung::create([
                'arbeitsgang_id' => $alt->arbeitsgang_id,
                'benutzer_id' => $alt->benutzer_id,
                'taetigkeit' => $taetigkeit,
                'beginn' => $von,
                'ende' => $bis,
                'quelle' => 'korrektur',
                'ersetzt_buchung_id' => $alt->id,
                'begruendung' => $begruendung,
                'erfasst_von' => $meister->id,
                'erfasst_am' => now(),
            ]);

            $alt->update(['gueltig' => false]);

            return $neu;
        });
    }

    public function nachtragen(Benutzer $meister, int $arbeitsgangId, int $werkerId, string $taetigkeit, string $beginn, string $ende, string $begruendung): Zeitbuchung
    {
        [$von, $bis, $begruendung] = $this->pruefeEingaben($taetigkeit, $beginn, $ende, $begruendung);

        if (! Benutzer::whereKey($werkerId)->exists()) {
            Fachfehler::melden('Bitte den Werker auswählen.', 'werkerId');
        }

        $arbeitsgang = Arbeitsgang::with('auftrag')->findOrFail($arbeitsgangId);
        $this->pruefeAuftrag($arbeitsgang);

        return Zeitbuchung::create([
            'arbeitsgang_id' => $arbeitsgang->id,
            'benutzer_id' => $werkerId,
            'taetigkeit' => $taetigkeit,
            'beginn' => $von,
            'ende' => $bis,
            'quelle' => 'nachtrag',
            'begruendung' => $begruendung,
            'erfasst_von' => $meister->id,
            'erfasst_am' => now(),
        ]);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string} */
    private function pruefeEingaben(string $taetigkeit, string $beginn, string $ende, string $begruendung): array
    {
        if (! in_array($taetigkeit, ['ruesten', 'bearbeiten'], true)) {
            Fachfehler::melden('Bitte Rüsten oder Bearbeiten wählen.', 'taetigkeit');
        }

        $begruendung = trim($begruendung);

        if ($begruendung === '') {
            Fachfehler::melden('Eine Begründung ist Pflicht, damit die Änderung nachvollziehbar bleibt.', 'begruendung');
        }

        try {
            $von = CarbonImmutable::parse($beginn)->startOfMinute();
            $bis = CarbonImmutable::parse($ende)->startOfMinute();
        } catch (\Throwable) {
            Fachfehler::melden('Bitte Beginn und Ende als Datum mit Uhrzeit eintragen.', 'ende');
        }

        if ($bis->lessThanOrEqualTo($von)) {
            Fachfehler::melden('Das Ende muss nach dem Beginn liegen.', 'ende');
        }

        if ($bis->greaterThan(now())) {
            Fachfehler::melden('Das Ende darf nicht in der Zukunft liegen.', 'ende');
        }

        return [$von, $bis, mb_substr($begruendung, 0, 255)];
    }

    private function pruefeAuftrag(Arbeitsgang $arbeitsgang): void
    {
        if (in_array($arbeitsgang->auftrag->status, ['nachkalkuliert', 'storniert'], true)) {
            Fachfehler::melden('Der Auftrag ist '.$arbeitsgang->auftrag->status.', Buchungen lassen sich nicht mehr ändern.');
        }
    }
}
