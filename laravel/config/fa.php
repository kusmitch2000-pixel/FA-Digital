<?php

// Fachliche Einstellungen von FA-Digital

return [

    // Ordner mit den SQL-Skripten des Datenmodells (Abgabe vom 12.10.2026).
    // Migrationen und Testdaten lesen 01_schema.sql, 02_testdaten.sql und 03_sichten.sql
    // direkt von dort. So gibt es das Datenmodell nur einmal.
    'sql_pfad' => env('FA_SQL_PFAD', base_path('../datenbank')),

    // Rollen wie in der Tabelle benutzer
    'rollen' => [
        'werker' => 'Werker',
        'meister' => 'Meister',
        'arbeitsvorbereitung' => 'Arbeitsvorbereitung',
        'controlling' => 'Controlling',
        'admin' => 'Admin',
    ],

    // Menü in dieser Reihenfolge. Jede Rolle sieht nur die Seiten, für die sie ein Recht hat
    // (Rückmeldung vom 01.10.2026: Informationen gebündelt für den richtigen Benutzer).
    'menue' => [
        'terminal' => 'Werker-Terminal',
        'halle' => 'Hallenansicht',
        'auftraege' => 'Aufträge',
        'auftrag.anlegen' => 'Auftrag anlegen',
        'zertifikate' => 'Werkszertifikate',
        'nachkalkulation' => 'Nachkalkulation',
        'auswertung' => 'Auswertung',
        'arbeitsplaene' => 'Artikel & Arbeitspläne',
        'arbeitsplaetze' => 'Arbeitsplätze',
        'benutzer' => 'Benutzer',
    ],

    // Wer was darf. Schlüssel sind Seiten (Routennamen) und Aktionen auf diesen Seiten.
    'rechte' => [
        'terminal' => ['werker', 'meister', 'admin'],
        'halle' => ['meister', 'arbeitsvorbereitung', 'controlling', 'admin'],
        'auftraege' => ['werker', 'meister', 'arbeitsvorbereitung', 'controlling', 'admin'],
        'auftrag.anlegen' => ['arbeitsvorbereitung', 'admin'],
        'auftrag.freigeben' => ['arbeitsvorbereitung', 'admin'],
        'buchung.korrigieren' => ['meister', 'admin'],
        'zertifikate' => ['meister', 'arbeitsvorbereitung', 'admin'],
        'zertifikat.freigeben' => ['meister', 'admin'],
        'nachkalkulation' => ['meister', 'controlling', 'admin'],
        'nachkalkulation.abschliessen' => ['controlling', 'admin'],
        'auswertung' => ['meister', 'controlling', 'admin'],
        'arbeitsplaene' => ['arbeitsvorbereitung', 'admin'],
        'arbeitsplaetze' => ['arbeitsvorbereitung', 'controlling', 'admin'],
        'benutzer' => ['admin'],
    ],

    // Startseite nach der Anmeldung
    'startseite' => [
        'werker' => 'terminal',
        'meister' => 'halle',
        'arbeitsvorbereitung' => 'auftraege',
        'controlling' => 'nachkalkulation',
        'admin' => 'halle',
    ],

];
