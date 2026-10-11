<?php

namespace App\Http\Controllers;

use App\Support\Kennzahlen;
use Illuminate\Http\Response;

/** CSV-Export der Auswertung für Excel: Semikolon, Dezimalkomma, UTF-8 mit BOM */
class CsvExportController extends Controller
{
    public function __invoke(string $bereich): Response
    {
        [$kopf, $zeilen] = match ($bereich) {
            'auftraege' => [
                ['Auftrag', 'Artikel-Nr.', 'Artikel', 'Menge', 'Gutmenge', 'Ausschuss', 'Abschluss', 'Soll-Zeit (min)', 'Ist-Zeit (min)',
                    'Soll-Kosten (EUR)', 'Ist-Kosten (EUR)', 'Abweichung (%)', 'Soll-Kosten je Stück (EUR)', 'Ist-Kosten je Gutstück (EUR)', 'Status'],
                Kennzahlen::auftraege()->map(fn ($a) => [
                    $a->auftragsnummer, $a->artikelnummer, $a->artikel, $a->menge, $a->gutmenge, $a->ausschuss,
                    $a->abgeschlossen_am ? datum($a->abgeschlossen_am) : '', $this->zahl($a->soll_zeit_min), $this->zahl($a->ist_zeit_min),
                    $this->zahl($a->soll_kosten, 2), $this->zahl($a->ist_kosten, 2), $this->zahl($a->abweichung_prozent, 1),
                    $this->zahl($a->soll_kosten_je_stueck, 2), $this->zahl($a->ist_kosten_je_gutstueck, 2), status_text($a->status),
                ]),
            ],
            'arbeitsplaetze' => [
                ['Arbeitsplatz-Nr.', 'Arbeitsplatz', 'Arbeitsgänge', 'Soll-Zeit (min)', 'Ist-Zeit (min)', 'Abweichung (%)'],
                Kennzahlen::arbeitsplaetze()->map(fn ($p) => [
                    $p->arbeitsplatz_nummer, $p->arbeitsplatz, $p->arbeitsgaenge, $this->zahl($p->soll_zeit_min),
                    $this->zahl($p->ist_zeit_min), $this->zahl($p->abweichung_prozent, 1),
                ]),
            ],
            'unterbrechungen' => [
                ['Grund', 'Geplant', 'Anzahl', 'Dauer (min)', 'Anteil (%)', 'Am häufigsten an'],
                Kennzahlen::unterbrechungen()->map(fn ($u) => [
                    $u->grund, $u->geplant ? 'ja' : 'nein', $u->anzahl, $u->dauer_min, $u->anteil_prozent, $u->haeufigster_arbeitsplatz,
                ]),
            ],
            default => abort(404),
        };

        $zeilen = collect([$kopf])->concat($zeilen)
            ->map(fn (array $felder) => implode(';', array_map(fn ($wert) => $this->feld($wert), $felder)));

        return response("\xEF\xBB\xBF".$zeilen->implode("\r\n")."\r\n", 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="fa-digital_'.$bereich.'_'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    private function zahl(mixed $wert, int $stellen = 0): string
    {
        return $wert === null ? '' : number_format((float) $wert, $stellen, ',', '');
    }

    /** Felder mit Semikolon, Anführungszeichen oder Zeilenumbruch in Anführungszeichen setzen */
    private function feld(mixed $wert): string
    {
        $text = (string) $wert;

        return preg_match('/[;"\r\n]/', $text) ? '"'.str_replace('"', '""', $text).'"' : $text;
    }
}
