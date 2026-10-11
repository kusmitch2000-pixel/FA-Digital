<?php

namespace App\Actions\Fertigung;

use App\Models\Arbeitsgang;
use App\Models\Benutzer;
use App\Models\Pruefpunkt;
use App\Models\Unterbrechung;
use App\Models\Unterbrechungsgrund;
use App\Models\Zeitbuchung;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Abläufe am Werker-Terminal (Anwendungsfälle 1 bis 5):
 * starten, Rüsten/Bearbeiten wechseln, unterbrechen, fortsetzen, Checkliste, fertig melden.
 * Jeder Ablauf läuft in einer Transaktion wie in datenbank/05_beispielablaeufe.sql.
 */
class Werkstatt
{
    /** Arbeitsgang, an dem der Werker gerade arbeitet oder den er unterbrochen hat */
    public function aktuellerArbeitsgang(Benutzer $werker): ?Arbeitsgang
    {
        return Arbeitsgang::with(['auftrag.artikel', 'arbeitsplatz'])
            ->whereIn('status', ['laeuft', 'unterbrochen'])
            ->where(function ($abfrage) use ($werker) {
                $abfrage->whereHas('zeitbuchungen', fn ($b) => $b->where('benutzer_id', $werker->id)->whereNull('ende')->where('gueltig', true))
                    ->orWhereHas('unterbrechungen', fn ($u) => $u->where('benutzer_id', $werker->id)->whereNull('ende'));
            })
            ->first();
    }

    public function starten(Benutzer $werker, int $arbeitsgangId, string $taetigkeit): void
    {
        $this->pruefeTaetigkeit($taetigkeit);

        DB::transaction(function () use ($werker, $arbeitsgangId, $taetigkeit) {
            $arbeitsgang = Arbeitsgang::with('auftrag')->lockForUpdate()->findOrFail($arbeitsgangId);

            if ($this->aktuellerArbeitsgang($werker)) {
                Fachfehler::melden('Sie haben schon einen Arbeitsgang begonnen. Bitte ihn erst fertig melden.');
            }

            // Die Sicht v_arbeitsvorrat entscheidet, ob der Arbeitsgang an der Reihe ist
            $bereitschaft = DB::table('v_arbeitsvorrat')->where('arbeitsgang_id', $arbeitsgang->id)->value('bereitschaft');

            if ($bereitschaft !== 'bereit') {
                Fachfehler::melden(match ($bereitschaft) {
                    'wartet' => 'Der vorherige Arbeitsgang ist noch nicht fertig gemeldet.',
                    'laeuft', 'unterbrochen' => 'An diesem Arbeitsgang arbeitet schon jemand.',
                    default => 'Dieser Arbeitsgang ist nicht freigegeben.',
                });
            }

            $platzBelegt = Arbeitsgang::where('arbeitsplatz_id', $arbeitsgang->arbeitsplatz_id)
                ->whereIn('status', ['laeuft', 'unterbrochen'])
                ->exists();

            if ($platzBelegt) {
                Fachfehler::melden('An diesem Arbeitsplatz läuft schon ein anderer Arbeitsgang.');
            }

            $this->buchungBeginnen($arbeitsgang, $werker, $taetigkeit, now());
            $arbeitsgang->update(['status' => 'laeuft']);

            if ($arbeitsgang->auftrag->status === 'freigegeben') {
                $arbeitsgang->auftrag->update(['status' => 'in_arbeit']);
            }
        });
    }

    /** Von Rüsten auf Bearbeiten wechseln oder umgekehrt */
    public function taetigkeitWechseln(Benutzer $werker, string $taetigkeit): void
    {
        $this->pruefeTaetigkeit($taetigkeit);

        DB::transaction(function () use ($werker, $taetigkeit) {
            $buchung = $this->laufendeBuchung($werker);

            if ($buchung->taetigkeit === $taetigkeit) {
                return;
            }

            $ende = $this->buchungBeenden($buchung, now());
            $this->buchungBeginnen($buchung->arbeitsgang, $werker, $taetigkeit, $ende);
        });
    }

    /** Die Unterbrechungszeit zählt nicht zur Ist-Zeit, sie wird nach Grund ausgewertet */
    public function unterbrechen(Benutzer $werker, int $grundId, ?string $bemerkung = null): void
    {
        $grund = Unterbrechungsgrund::where('aktiv', true)->find($grundId)
            ?? Fachfehler::melden('Bitte einen Grund auswählen.', 'grund');

        DB::transaction(function () use ($werker, $grund, $bemerkung) {
            $buchung = $this->laufendeBuchung($werker);
            $ende = $this->buchungBeenden($buchung, now());

            Unterbrechung::create([
                'arbeitsgang_id' => $buchung->arbeitsgang_id,
                'benutzer_id' => $werker->id,
                'unterbrechungsgrund_id' => $grund->id,
                'beginn' => $ende,
                'bemerkung' => $bemerkung ?: null,
            ]);

            $buchung->arbeitsgang->update(['status' => 'unterbrochen']);
        });
    }

    /** Nach einer Unterbrechung mit derselben Tätigkeit weitermachen */
    public function fortsetzen(Benutzer $werker): void
    {
        DB::transaction(function () use ($werker) {
            $unterbrechung = Unterbrechung::where('benutzer_id', $werker->id)->whereNull('ende')->lockForUpdate()->first()
                ?? Fachfehler::melden('Es gibt keine offene Unterbrechung.');

            $ende = $this->spaetestens($unterbrechung->beginn, now());
            $unterbrechung->update(['ende' => $ende]);

            $arbeitsgang = Arbeitsgang::findOrFail($unterbrechung->arbeitsgang_id);
            $taetigkeit = $arbeitsgang->zeitbuchungen()->where('gueltig', true)->reorder()->orderByDesc('id')->value('taetigkeit') ?? 'bearbeiten';

            $this->buchungBeginnen($arbeitsgang, $werker, $taetigkeit, $ende);
            $arbeitsgang->update(['status' => 'laeuft']);
        });
    }

    /**
     * Ergebnis eines Prüfpunkts eintragen (Anwendungsfall 4). Bei Messwerten ergibt sich
     * „i. O.“ oder „n. i. O.“ aus den Toleranzgrenzen. Nach Nacharbeit darf erneut gemessen werden.
     */
    public function pruefen(Benutzer $werker, int $pruefpunktId, ?string $istwert = null, ?string $ergebnis = null, ?string $bemerkung = null): void
    {
        $pruefpunkt = Pruefpunkt::findOrFail($pruefpunktId);

        if ($this->aktuellerArbeitsgang($werker)?->id !== $pruefpunkt->arbeitsgang_id) {
            Fachfehler::melden('Prüfpunkte lassen sich nur im eigenen laufenden Arbeitsgang erfassen.');
        }

        $feld = 'istwert.'.$pruefpunkt->id;

        if ($pruefpunkt->art === 'messwert') {
            $text = str_replace(',', '.', trim((string) $istwert));

            if (! is_numeric($text) || abs((float) $text) >= 1e7) {
                Fachfehler::melden('Bitte einen gültigen Messwert eintragen.', $feld);
            }

            $wert = round((float) $text, 3);
            $ergebnis = $wert >= round((float) $pruefpunkt->untere_grenze, 3) && $wert <= round((float) $pruefpunkt->obere_grenze, 3)
                ? 'in_ordnung' : 'nicht_in_ordnung';
        } else {
            $wert = null;

            if (! in_array($ergebnis, ['in_ordnung', 'nicht_in_ordnung'], true)) {
                Fachfehler::melden('Bitte „i. O.“ oder „n. i. O.“ wählen.', $feld);
            }
        }

        $pruefpunkt->update([
            'istwert' => $wert,
            'ergebnis' => $ergebnis,
            'bemerkung' => $bemerkung ?: null,
            'geprueft_von' => $werker->id,
            'geprueft_am' => now(),
        ]);
    }

    /** Arbeitsgang fertig melden. Geht erst, wenn kein Prüfpunkt mehr offen ist. */
    public function fertigMelden(Benutzer $werker, int $gutmenge, int $ausschuss, ?string $bemerkung = null): void
    {
        DB::transaction(function () use ($werker, $gutmenge, $ausschuss, $bemerkung) {
            $buchung = $this->laufendeBuchung($werker);
            $arbeitsgang = Arbeitsgang::with('auftrag')->lockForUpdate()->findOrFail($buchung->arbeitsgang_id);

            $offen = $arbeitsgang->pruefpunkte()->where('ergebnis', 'offen')->count();

            if ($offen > 0) {
                Fachfehler::melden("Die Checkliste ist noch nicht fertig: {$offen} ".($offen === 1 ? 'Prüfpunkt ist' : 'Prüfpunkte sind').' offen.');
            }

            if ($gutmenge < 0 || $ausschuss < 0 || $gutmenge + $ausschuss === 0) {
                Fachfehler::melden('Bitte Gutmenge und Ausschuss eintragen.', 'gutmenge');
            }

            if ($gutmenge + $ausschuss > $arbeitsgang->auftrag->menge) {
                Fachfehler::melden("Gutmenge und Ausschuss zusammen sind größer als die Auftragsmenge ({$arbeitsgang->auftrag->menge} Stück).", 'gutmenge');
            }

            $ende = $this->buchungBeenden($buchung, now());

            $arbeitsgang->update([
                'status' => 'erledigt',
                'gutmenge' => $gutmenge,
                'ausschuss' => $ausschuss,
                'fertig_gemeldet_von' => $werker->id,
                'fertig_gemeldet_am' => $ende,
                'bemerkung' => $bemerkung ?: null,
            ]);

            // Letzter Arbeitsgang erledigt: Auftrag ist fertig und geht in die Nachkalkulation
            if (! $arbeitsgang->auftrag->arbeitsgaenge()->where('status', '<>', 'erledigt')->exists()) {
                $arbeitsgang->auftrag->update(['status' => 'fertig', 'abgeschlossen_von' => $werker->id, 'abgeschlossen_am' => $ende]);
            }
        });
    }

    private function laufendeBuchung(Benutzer $werker): Zeitbuchung
    {
        return Zeitbuchung::with('arbeitsgang')
            ->where('benutzer_id', $werker->id)->whereNull('ende')->where('gueltig', true)
            ->lockForUpdate()->first()
            ?? Fachfehler::melden('Es läuft gerade keine Buchung. Ein unterbrochener Arbeitsgang muss erst fortgesetzt werden.');
    }

    private function buchungBeginnen(Arbeitsgang $arbeitsgang, Benutzer $werker, string $taetigkeit, CarbonInterface $beginn): void
    {
        Zeitbuchung::create([
            'arbeitsgang_id' => $arbeitsgang->id,
            'benutzer_id' => $werker->id,
            'taetigkeit' => $taetigkeit,
            'beginn' => $beginn,
            'quelle' => 'terminal',
            'erfasst_von' => $werker->id,
            'erfasst_am' => $beginn,
        ]);
    }

    private function buchungBeenden(Zeitbuchung $buchung, CarbonInterface $jetzt): CarbonInterface
    {
        $ende = $this->spaetestens($buchung->beginn, $jetzt);
        $buchung->update(['ende' => $ende]);

        return $ende;
    }

    /** Die Datenbank verlangt ende > beginn. Zwei Klicks in derselben Sekunde ergeben deshalb eine Sekunde Dauer. */
    private function spaetestens(CarbonInterface $beginn, CarbonInterface $jetzt): CarbonInterface
    {
        $jetzt = $jetzt->startOfSecond();

        return $jetzt->greaterThan($beginn) ? $jetzt : $beginn->addSecond();
    }

    private function pruefeTaetigkeit(string $taetigkeit): void
    {
        if (! in_array($taetigkeit, ['ruesten', 'bearbeiten'], true)) {
            Fachfehler::melden('Unbekannte Tätigkeit.');
        }
    }
}
