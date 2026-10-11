<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Führt die SQL-Skripte aus dem Ordner datenbank/ aus.
 *
 * Die Skripte sind die Abgabe zum Datenmodell und laufen auch ohne Laravel in phpMyAdmin.
 * Laravel nutzt sie unverändert, überspringt aber DROP/CREATE DATABASE und USE,
 * weil die Datenbank aus der .env schon besteht (z. B. fa_digital_test für die Tests).
 */
class SqlSkript
{
    public static function pfad(string $datei): string
    {
        $pfad = rtrim(config('fa.sql_pfad'), '/\\').DIRECTORY_SEPARATOR.$datei;

        if (! is_file($pfad)) {
            throw new RuntimeException("SQL-Skript nicht gefunden: {$pfad}. Ordner in FA_SQL_PFAD prüfen.");
        }

        return $pfad;
    }

    public static function inhalt(string $datei): string
    {
        $inhalt = file_get_contents(self::pfad($datei));

        if ($inhalt === false) {
            throw new RuntimeException("SQL-Skript nicht lesbar: {$datei}");
        }

        return $inhalt;
    }

    public static function ausfuehren(string $datei): void
    {
        // Die Skripte stammen aus dem Repository, nicht aus Eingaben. Deshalb direkt über PDO,
        // Anweisung für Anweisung, damit ein Fehler sofort mit der richtigen Stelle auffällt.
        $pdo = DB::connection()->getPdo();

        foreach (self::anweisungen(self::inhalt($datei)) as $sql) {
            if (preg_match('/^(DROP\s+DATABASE|CREATE\s+DATABASE|USE)\b/i', $sql)) {
                continue;
            }

            $pdo->exec($sql);
        }
    }

    /** @return list<string> Namen aller Sichten, die ein Skript anlegt */
    public static function sichten(string $datei): array
    {
        preg_match_all('/CREATE\s+OR\s+REPLACE\s+VIEW\s+(\w+)/i', self::inhalt($datei), $treffer);

        return $treffer[1];
    }

    /** @return list<string> Namen aller Tabellen, die ein Skript anlegt */
    public static function tabellen(string $datei): array
    {
        preg_match_all('/CREATE\s+TABLE\s+(\w+)/i', self::inhalt($datei), $treffer);

        return $treffer[1];
    }

    /**
     * Zerlegt ein Skript in einzelne Anweisungen. Ein Semikolon trennt nur außerhalb
     * von Zeichenketten und Kommentaren.
     *
     * @return list<string>
     */
    public static function anweisungen(string $skript): array
    {
        $anweisungen = [];
        $aktuell = '';
        $laenge = strlen($skript);

        for ($i = 0; $i < $laenge; $i++) {
            $zeichen = $skript[$i];
            $naechstes = $skript[$i + 1] ?? '';

            // Kommentar bis Zeilenende: "-- " oder "#"
            if (($zeichen === '-' && $naechstes === '-' && in_array($skript[$i + 2] ?? "\n", [' ', "\t", "\r", "\n"], true)) || $zeichen === '#') {
                $ende = strpos($skript, "\n", $i);
                $i = $ende === false ? $laenge : $ende;
                $aktuell .= "\n";

                continue;
            }

            // Blockkommentar
            if ($zeichen === '/' && $naechstes === '*') {
                $ende = strpos($skript, '*/', $i + 2);
                $i = $ende === false ? $laenge : $ende + 1;

                continue;
            }

            // Zeichenkette in '', "" oder `` unverändert übernehmen
            if ($zeichen === "'" || $zeichen === '"' || $zeichen === '`') {
                $aktuell .= $zeichen;

                for ($i++; $i < $laenge; $i++) {
                    $aktuell .= $skript[$i];

                    if ($skript[$i] === '\\' && $zeichen !== '`') {
                        $aktuell .= $skript[++$i] ?? '';
                    } elseif ($skript[$i] === $zeichen) {
                        if (($skript[$i + 1] ?? '') !== $zeichen) {
                            break;
                        }
                        $aktuell .= $skript[++$i];
                    }
                }

                continue;
            }

            if ($zeichen === ';') {
                if (trim($aktuell) !== '') {
                    $anweisungen[] = trim($aktuell);
                }
                $aktuell = '';

                continue;
            }

            $aktuell .= $zeichen;
        }

        if (trim($aktuell) !== '') {
            $anweisungen[] = trim($aktuell);
        }

        return $anweisungen;
    }
}
