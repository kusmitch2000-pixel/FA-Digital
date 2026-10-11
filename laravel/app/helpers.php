<?php

// Anzeige von Zahlen, Zeiten und Daten im deutschen Format, z. B. in den Blade-Seiten

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (! function_exists('zahl')) {
    function zahl(int|float|string|null $wert, int $stellen = 0): string
    {
        return $wert === null || $wert === '' ? '–' : number_format((float) $wert, $stellen, ',', '.');
    }
}

if (! function_exists('euro')) {
    function euro(int|float|string|null $wert): string
    {
        return $wert === null || $wert === '' ? '–' : zahl($wert, 2).' €';
    }
}

if (! function_exists('minuten')) {
    function minuten(int|float|string|null $wert): string
    {
        return $wert === null || $wert === '' ? '–' : zahl($wert, fmod((float) $wert, 1.0) == 0.0 ? 0 : 1).' min';
    }
}

if (! function_exists('prozent')) {
    /** Abweichung mit Vorzeichen, z. B. +8,7 % */
    function prozent(int|float|string|null $wert, bool $vorzeichen = true): string
    {
        if ($wert === null || $wert === '') {
            return '–';
        }

        return ($vorzeichen && (float) $wert > 0 ? '+' : '').zahl($wert, 1).' %';
    }
}

if (! function_exists('zeitpunkt')) {
    function zeitpunkt(CarbonInterface|string|null $wert, string $format = 'd.m.Y H:i'): string
    {
        if ($wert === null || $wert === '') {
            return '–';
        }

        return ($wert instanceof CarbonInterface ? $wert : Carbon::parse($wert))->format($format);
    }
}

if (! function_exists('datum')) {
    function datum(CarbonInterface|string|null $wert): string
    {
        return zeitpunkt($wert, 'd.m.Y');
    }
}

if (! function_exists('uhrzeit')) {
    function uhrzeit(CarbonInterface|string|null $wert): string
    {
        return zeitpunkt($wert, 'H:i');
    }
}

if (! function_exists('status_klasse')) {
    /** CSS-Klasse des Badges für einen Statuswert, Farben wie im Prototyp */
    function status_klasse(?string $status): string
    {
        return [
            'in_arbeit' => 'in-arbeit',
            'in_ordnung' => 'erledigt',
            'nicht_in_ordnung' => 'verzug',
            'gefordert' => 'offen',
            'erstellt' => 'angelegt',
            'bereit' => 'erledigt',
            'wartet' => 'offen',
        ][$status] ?? (string) $status;
    }
}

if (! function_exists('status_text')) {
    /** Statuswerte aus der Datenbank lesbar machen, z. B. in_arbeit → in Arbeit */
    function status_text(?string $status): string
    {
        return [
            'in_arbeit' => 'in Arbeit',
            'laeuft' => 'läuft',
            'in_ordnung' => 'i. O.',
            'nicht_in_ordnung' => 'n. i. O.',
            'ruesten' => 'Rüsten',
            'bearbeiten' => 'Bearbeiten',
            'sichtpruefung' => 'Sichtprüfung',
            'messwert' => 'Messwert',
        ][$status] ?? (string) $status;
    }
}
