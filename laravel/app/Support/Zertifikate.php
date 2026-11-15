<?php

namespace App\Support;

use App\Models\Zertifikatsart;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Für welche Artikel ein Zertifikat gilt, ergibt sich aus den Prüfmerkmalen im Arbeitsplan:
 * Ein Zertifikat mit Prüfmerkmalen gilt für die Artikel, in deren Arbeitsplan diese Merkmale stehen.
 * Ein Zertifikat ohne eigene Prüfmerkmale (z. B. Werksbescheinigung 2.1) gilt für alle Artikel.
 * So gibt es die Zuordnung nur an einer Stelle und das Datenmodell bleibt unverändert.
 */
class Zertifikate
{
    /** Kürzel der Zertifikate nach DIN EN 10204. Alle anderen legt die Arbeitsvorbereitung selbst an. */
    public const NORM = ['2.1', '2.2', '3.1'];

    /** @return Collection<int, Zertifikatsart> Zertifikate, die für diesen Artikel gefordert werden können */
    public static function fuerArtikel(?int $artikelId): Collection
    {
        $mitMerkmalen = DB::table('pruefmerkmal')->whereNotNull('zertifikatsart_id')->distinct()->pluck('zertifikatsart_id');
        $fuerArtikel = DB::table('pruefmerkmal')
            ->join('arbeitsplan_position', 'arbeitsplan_position.id', '=', 'pruefmerkmal.arbeitsplan_position_id')
            ->where('arbeitsplan_position.artikel_id', $artikelId ?? 0)
            ->whereNotNull('pruefmerkmal.zertifikatsart_id')
            ->distinct()
            ->pluck('pruefmerkmal.zertifikatsart_id');

        return Zertifikatsart::where(fn ($abfrage) => $abfrage->whereNotIn('id', $mitMerkmalen)->orWhereIn('id', $fuerArtikel))
            ->orderBy('kuerzel')
            ->get();
    }

    public static function istNorm(Zertifikatsart $zertifikat): bool
    {
        return in_array($zertifikat->kuerzel, self::NORM, true);
    }
}
