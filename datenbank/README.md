# Datenbank FA-Digital

MariaDB 10.4 aus XAMPP, Datenbank `fa_digital`. Die Beschreibung des Datenmodells steht in `doku/Datenmodell_FA-Digital.pdf`.

## Skripte

| Datei | Inhalt |
|---|---|
| `01_schema.sql` | legt die Datenbank mit 14 Tabellen, 27 Fremdschlüsseln und Prüfregeln an (löscht eine vorhandene `fa_digital`) |
| `02_testdaten.sql` | Beispieldaten passend zum Prototyp, Zeitpunkte relativ zum Import |
| `03_sichten.sql` | 14 Sichten für Hallenansicht, Arbeitsvorrat, Checkliste, Nachkalkulation, Auswertung und Soll-Zeit-Vorschläge |
| `04_abfragen.sql` | lesende Beispielabfragen je Anwendungsfall |
| `05_beispielablaeufe.sql` | acht schreibende Abläufe als Transaktion mit ROLLBACK, die Testdaten bleiben unverändert |
| `hilfsskripte/erzeuge-testdaten.js` | erzeugt `02_testdaten.sql` neu (`node erzeuge-testdaten.js`) |

## Import

XAMPP Control Panel öffnen und Apache und MySQL starten. Dann in phpMyAdmin unter „Importieren“ die Dateien 01, 02 und 03 nacheinander einspielen. Schritt für Schritt mit Datensätze ansehen und Fehlerhilfe: `anleitung-phpmyadmin.md`. Alternativ in der Kommandozeile:

```
cd C:\xampp\mysql\bin
mysql -u root --default-character-set=utf8mb4 < C:\Pfad\zu\datenbank\01_schema.sql
mysql -u root --default-character-set=utf8mb4 fa_digital < C:\Pfad\zu\datenbank\02_testdaten.sql
mysql -u root --default-character-set=utf8mb4 fa_digital < C:\Pfad\zu\datenbank\03_sichten.sql
```

## Test-Benutzer

Alle haben das Passwort `fa-digital` (nur für die Entwicklung).

| E-Mail | Rolle |
|---|---|
| sven.petersen@fa-digital.de | Admin |
| anna.becker@fa-digital.de | Arbeitsvorbereitung |
| lena.hoffmann@fa-digital.de | Meister |
| tobias.krueger@fa-digital.de | Controlling |
| jonas.wolff@fa-digital.de, mehmet.yilmaz@fa-digital.de, paul.schneider@fa-digital.de | Werker |
