<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/** Lesende Abfragen auf die Sichten für Auswertung und CSV-Export (Anwendungsfall 11) */
class Kennzahlen
{
    /** @return Collection<int, stdClass> abgeschlossene Aufträge mit Soll-Ist-Vergleich, neueste zuerst */
    public static function auftraege(): Collection
    {
        return DB::table('v_nachkalkulation_auftrag')
            ->whereIn('status', ['fertig', 'nachkalkuliert'])
            ->orderByDesc('abgeschlossen_am')
            ->get();
    }

    /** @return Collection<int, stdClass> */
    public static function arbeitsplaetze(): Collection
    {
        return DB::table('v_zeitabweichung_arbeitsplatz')->orderBy('arbeitsplatz_nummer')->get();
    }

    /** @return Collection<int, stdClass> Unterbrechungen nach Grund. Der Anteil gilt getrennt für ungeplante Gründe und Pausen. */
    public static function unterbrechungen(): Collection
    {
        return DB::table('v_unterbrechung_auswertung')
            ->select('*')
            ->selectRaw('ROUND(100 * dauer_min / NULLIF(SUM(dauer_min) OVER (PARTITION BY geplant), 0)) AS anteil_prozent')
            ->orderBy('geplant')
            ->orderByDesc('dauer_min')
            ->get();
    }
}
