# FA-Digital: Laravel-Anwendung

Laravel 13 mit Livewire 4 und Fortify (Anmeldung), Datenbank MariaDB. Die Oberfläche übernimmt Farben und Aufbau aus `../prototyp/`.

Die Migrationen führen die SQL-Skripte aus `../datenbank/` aus (Abgabe vom 12.10.2026); weitere Laravel-Migrationen ergänzen Zuordnungen, Notizen und den Status „Messen“. Tabellen und Sichten heißen in der App genauso wie im Fachkonzept zum Datenmodell. Soll- und Ist-Zeiten, Kosten, Ampeln und Vorschläge rechnen die Sichten. PHP liest diese Werte nur.

## Aufbau

| Ordner | Inhalt |
|---|---|
| `app/Livewire/` | eine Livewire-Komponente je Seite, Blade-Vorlagen in `resources/views/livewire/` |
| `app/Actions/Fertigung/` | Fachlogik als eigene Klassen: Auftrag anlegen, Werker-Terminal, Buchungskorrektur, Statuswechsel, Soll-Zeit übernehmen |
| `app/Models/` | 14 Eloquent-Models auf die Fachtabellen, z. B. `Fertigungsauftrag`, `Arbeitsgang`, `Zeitbuchung` |
| `app/Support/` | `SqlSkript` (führt die Skripte aus `datenbank/` aus), `Kennzahlen` (Abfragen für Auswertung und CSV) |
| `config/fa.php` | Rollen, Menü und Rechte: welche Rolle welche Seite sieht und was sie dort darf |
| `database/migrations/` | technische Tabellen von Laravel (`sessions`, `cache`, `jobs`) plus die Fachtabellen und Sichten aus `datenbank/` |
| `database/seeders/` | lädt `datenbank/02_testdaten.sql` |
| `lang/de/` | deutsche Fehlermeldungen |
| `public/css/production.css` | Gestaltung aus dem Prototyp |
| `tests/` | Feature-Tests für alle Abläufe |

Das persönliche Werker-Terminal und seine Zuordnungen sind in der [Werker-Anleitung](../docs/werker-terminal.md) beschrieben.

## Seiten und Anwendungsfälle

Jede Rolle sieht nur ihre Bereiche (Rückmeldung vom 01.10.2026). Nach der Anmeldung landet sie direkt auf ihrer Startseite.

| Adresse | Seite | Anwendungsfall | Rollen |
|---|---|---|---|
| `/meister` | Startseite mit überfälligen offenen Aufträgen und freigabebereiten Zertifikaten | – | Meister (Start) |
| `/werker` | Arbeitsauftragsvorlauf mit zugeordneten Arbeitsgängen | 1 bis 5 | Werker (Start), Meister, Admin |
| `/werker/aktiv` | Aktiver Arbeitsauftrag: Zeit, Unterbrechung, Notizen, Checkliste und Fertigmeldung | 1 bis 5 | Werker, Meister, Admin |
| `/halle` | Hallenansicht mit Ampel, Handlungsbedarf, Aufträge in der Werkstatt (Meister-Ansicht), letzte Buchungen. Lädt sich alle 30 s neu | 7 | Meister, AV, Controlling, Admin |
| `/auftraege` | Auftragsübersicht mit Suche und Statusfilter | 8 | Meister, AV, Controlling, Admin |
| `/auftraege/FA-1002` | Auftragsdetail: Arbeitsgänge, Zuordnungen, Checkliste, Zertifikate und Buchungen. AV bearbeitet Menge, Termin, Kunde und Zertifikate, solange niemand daran gearbeitet hat | 6, 8 | Meister, AV, Controlling, Admin; Aktionen je Rolle |
| `/auftraege/neu` | Auftrag anlegen mit geforderten Werkszertifikaten und Vorschau der Checkliste | Rückmeldung | AV (Start: Aufträge), Admin |
| `/zertifikate` | Zertifikatsfreigabe: Werkszertifikate prüfen, freigeben, als Werkszeugnis drucken | 9 | Meister, AV, Admin |
| `/zertifikatsvorlagen` | Zertifikatsvorlagen: eigene Zertifikate anlegen, ändern, löschen. Gilt für die Artikel, in deren Arbeitsplan es Prüfmerkmale hat | Rückmeldung | AV, Admin (Meister liest) |
| `/nachkalkulation` | Soll-Ist-Vergleich je Arbeitsgang und Auftrag, Nachkalkulation abschließen | 10 | Controlling (Start), Meister, Admin |
| `/auswertung` | abgeschlossene Aufträge, Abweichung je Arbeitsplatz, Unterbrechungen nach Grund, CSV-Export | 11 | Controlling, Meister, Admin |
| `/artikel` | Arbeitspläne: Prüfmerkmale je Arbeitsgang pflegen (immer oder nur bei einem Zertifikat), Soll-Zeit-Vorschläge übernehmen oder Soll-Zeiten von Hand ändern (mit Pflicht-Begründung, beides in der Historie) | Rückmeldung | AV, Admin |
| `/arbeitsplaetze` | Arbeitsplätze und Stundensätze | – | AV, Controlling, Admin |
| `/benutzer` | Benutzer anlegen, sperren, Passwort setzen, Rechte-Übersicht | 12 | Admin |

Das Meister-Menü enthält Startseite, Hallenansicht und Zertifikatsfreigaben. Links aus der Hallenansicht und der Startseite führen weiterhin zum Auftragsdetail; bestehende Berechtigungen werden durch die Menüauswahl nicht geändert. Auf der Startseite sind nur Zertifikate aufgeführt, deren Auftrag fertig ist und deren Prüfungen in Ordnung sind. Das gesamte Zertifikatsregister bleibt über „Zertifikatsfreigaben“ erreichbar.

## Entwicklung mit Docker

Die Einrichtung, tägliche Arbeit, Tests und Datensicherung sind in der [Docker-Team-Anleitung](../docs/docker-team-guide.md) beschrieben. Die [Migration](../docs/docker-migration.md) erklärt die Architektur und den Umgang mit bestehenden Daten.

## Test-Benutzer

Aus `datenbank/02_testdaten.sql`, alle mit dem Passwort `fa-digital` (nur für die Entwicklung):

| E-Mail | Rolle |
|---|---|
| sven.petersen@fa-digital.de | Admin |
| anna.becker@fa-digital.de | Arbeitsvorbereitung |
| lena.hoffmann@fa-digital.de | Meister |
| tobias.krueger@fa-digital.de | Controlling |
| Tätigkeitskonten wie drehen@fa-digital.de und lasern@fa-digital.de | Werker |

Ein eigenes Admin-Konto ohne Testdaten legt `php artisan fa:admin name@firma.de` an.

## Tests und Prüfwerkzeuge

Die Tests benötigen eine isolierte MariaDB-Datenbank. Die sicheren Docker-Befehle stehen in der Team-Anleitung.

## Zeitzone

Laufende Buchungen rechnet die Datenbank mit `NOW()`, die App schreibt Zeitpunkte mit der Uhrzeit von PHP. Beide müssen dieselbe Zeitzone haben. Die App nutzt `Europe/Berlin` (`APP_TIMEZONE`), XAMPP die Uhrzeit von Windows, Docker setzt `TZ=Europe/Berlin`.
