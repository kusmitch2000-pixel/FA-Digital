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
        'meister.startseite' => 'Startseite',
        'terminal' => 'Arbeitsauftragsvorlauf',
        'terminal.aktiv' => 'Aktiver Arbeitsauftrag',
        'halle' => 'Hallenansicht',
        'auftraege' => 'Aufträge',
        'auftrag.anlegen' => 'Auftrag anlegen',
        'zertifikate' => 'Zertifikatsfreigaben',
        'nachkalkulation' => 'Nachkalkulation',
        'auswertung' => 'Auswertung',
        'arbeitsplaene' => 'Artikel & Arbeitspläne',
        'zertifikatsvorlagen' => 'Zertifikatsvorlagen',
        'arbeitsplaetze' => 'Arbeitsplätze',
        'benutzer' => 'Benutzer',
    ],

    // Die Meister-Navigation zeigt nur die gewünschten Hauptseiten. Detail-Links und Rechte bleiben erhalten.
    'menue_pro_rolle' => [
        'meister' => ['meister.startseite', 'halle', 'zertifikate'],
    ],

    // Wer was darf. Schlüssel sind Seiten (Routennamen) und Aktionen auf diesen Seiten.
    'rechte' => [
        'meister.startseite' => ['meister'],
        'terminal' => ['werker', 'meister', 'admin'],
        'terminal.aktiv' => ['werker', 'meister', 'admin'],
        'halle' => ['meister', 'arbeitsvorbereitung', 'controlling', 'admin'],
        'auftraege' => ['meister', 'arbeitsvorbereitung', 'controlling', 'admin'],
        'auftrag.anlegen' => ['arbeitsvorbereitung', 'admin'],
        'auftrag.zuordnen' => ['meister', 'arbeitsvorbereitung', 'admin'],
        'auftrag.freigeben' => ['arbeitsvorbereitung', 'admin'],
        'buchung.korrigieren' => ['meister', 'admin'],
        'zertifikate' => ['meister', 'arbeitsvorbereitung', 'admin'],
        'zertifikat.freigeben' => ['meister', 'admin'],
        'nachkalkulation' => ['meister', 'controlling', 'admin'],
        'nachkalkulation.abschliessen' => ['controlling', 'admin'],
        'auswertung' => ['meister', 'controlling', 'admin'],
        'arbeitsplaene' => ['arbeitsvorbereitung', 'admin'],
        'zertifikatsvorlagen' => ['meister', 'arbeitsvorbereitung', 'admin'],
        'zertifikatsvorlage.anlegen' => ['arbeitsvorbereitung', 'admin'],
        'arbeitsplaetze' => ['arbeitsvorbereitung', 'controlling', 'admin'],
        'benutzer' => ['admin'],
    ],

    // Startseite nach der Anmeldung
    'startseite' => [
        'werker' => 'terminal',
        'meister' => 'meister.startseite',
        'arbeitsvorbereitung' => 'auftraege',
        'controlling' => 'nachkalkulation',
        'admin' => 'halle',
    ],

];
