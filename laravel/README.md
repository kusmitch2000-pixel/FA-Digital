# FA-Digital: Laravel-Anwendung

Laravel 13 mit Livewire 4 und Fortify (Anmeldung), Datenbank MariaDB. Die Oberfläche übernimmt Farben und Aufbau aus `../prototyp/`.

Das Datenmodell gibt es nur einmal: Die Migrationen führen die SQL-Skripte aus `../datenbank/` aus (Abgabe vom 12.10.2026). Tabellen und Sichten heißen in der App genauso wie im Fachkonzept zum Datenmodell. Soll- und Ist-Zeiten, Kosten, Ampeln und Vorschläge rechnen die Sichten. PHP liest diese Werte nur.

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

## Seiten und Anwendungsfälle

Jede Rolle sieht nur ihre Bereiche (Rückmeldung vom 01.10.2026). Nach der Anmeldung landet sie direkt auf ihrer Startseite.

| Adresse | Seite | Anwendungsfall | Rollen |
|---|---|---|---|
| `/werker` | Werker-Terminal: Arbeitsvorrat, Rüsten/Bearbeiten, Unterbrechung mit Grund, Checkliste, Fertigmeldung | 1 bis 5 | Werker (Start), Meister, Admin |
| `/halle` | Hallenansicht mit Ampel, Handlungsbedarf, Aufträge in der Werkstatt (Meister-Ansicht), letzte Buchungen. Lädt sich alle 30 s neu | 7 | Meister (Start), AV, Controlling, Admin |
| `/auftraege` | Auftragsübersicht mit Suche und Statusfilter | 8 | alle |
| `/auftraege/FA-1002` | Auftragsdetail: Arbeitsgänge, Checkliste, Zertifikate, Buchungen. Meister trägt nach und korrigiert, AV gibt frei | 6, 8 | alle, Aktionen je Rolle |
| `/auftraege/neu` | Auftrag anlegen mit geforderten Werkszertifikaten und Vorschau der Checkliste | Rückmeldung | AV (Start: Aufträge), Admin |
| `/zertifikate` | Werkszertifikate prüfen, freigeben, als Werkszeugnis drucken | 9 | Meister, AV, Admin |
| `/nachkalkulation` | Soll-Ist-Vergleich je Arbeitsgang und Auftrag, Nachkalkulation abschließen | 10 | Controlling (Start), Meister, Admin |
| `/auswertung` | abgeschlossene Aufträge, Abweichung je Arbeitsplatz, Unterbrechungen nach Grund, CSV-Export | 11 | Controlling, Meister, Admin |
| `/artikel` | Arbeitspläne mit Prüfmerkmalen, Soll-Zeit-Vorschläge übernehmen | Rückmeldung | AV, Admin |
| `/arbeitsplaetze` | Arbeitsplätze und Stundensätze | – | AV, Controlling, Admin |
| `/benutzer` | Benutzer anlegen, sperren, Passwort setzen, Rechte-Übersicht | 12 | Admin |

## Starten mit Docker

Im Ordner `laravel/`:

```
docker compose up --build -d
```

Dann <http://localhost:8000> öffnen. Beim ersten Start legt der Container das Datenmodell an und lädt die Testdaten. `docker compose logs -f app` zeigt Startfehler, `docker compose down` hält alles an. Die Daten bleiben in Docker-Volumes erhalten.

Wer die Docker-Version vom 27.09. schon einmal gestartet hat: Die alte Datenbank hatte andere Tabellen. Einmal mit `docker compose down -v` die Volumes löschen, dann neu starten.

## Starten ohne Docker (XAMPP)

Laravel 13 braucht PHP 8.3 oder neuer. Das PHP aus XAMPP 8.2 reicht nicht, die MariaDB aus XAMPP schon.

1. PHP 8.4 installieren, z. B. `winget install PHP.PHP.8.4`. In der `php.ini` die Erweiterungen `curl`, `fileinfo`, `intl`, `mbstring`, `openssl`, `pdo_mysql` und `zip` einschalten. Dazu Composer von <https://getcomposer.org>
2. Im XAMPP Control Panel MySQL starten
3. Im Ordner `laravel/`:

   ```
   composer install
   copy .env.example .env
   php artisan key:generate
   php artisan migrate:fresh --seed
   php artisan serve
   ```

4. <http://localhost:8000> öffnen

`.env.example` ist auf XAMPP eingestellt (Benutzer `root` ohne Passwort, Datenbank `fa_digital`). Wurden die SQL-Skripte schon in phpMyAdmin importiert, reicht `php artisan migrate`. Laravel übernimmt dann die vorhandenen Tabellen und legt nur seine technischen Tabellen dazu. `php artisan migrate:fresh --seed` setzt alles auf die Testdaten zurück, mit Zeitpunkten passend zu jetzt.

## Test-Benutzer

Aus `datenbank/02_testdaten.sql`, alle mit dem Passwort `fa-digital` (nur für die Entwicklung):

| E-Mail | Rolle |
|---|---|
| sven.petersen@fa-digital.de | Admin |
| anna.becker@fa-digital.de | Arbeitsvorbereitung |
| lena.hoffmann@fa-digital.de | Meister |
| tobias.krueger@fa-digital.de | Controlling |
| jonas.wolff@fa-digital.de, mehmet.yilmaz@fa-digital.de, paul.schneider@fa-digital.de | Werker |

Ein eigenes Admin-Konto ohne Testdaten legt `php artisan fa:admin name@firma.de` an.

## Tests und Prüfwerkzeuge

Die Tests brauchen MariaDB, weil das Datenmodell Sichten und CHECK-Regeln nutzt. Einmalig die Test-Datenbank anlegen:

```
C:\xampp\mysql\bin\mysql -u root -e "CREATE DATABASE fa_digital_test CHARACTER SET utf8mb4 COLLATE utf8mb4_german2_ci"
```

Dann:

```
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

`php artisan test` baut `fa_digital_test` aus den SQL-Skripten auf und spielt alle Abläufe durch, unter anderem Auftrag mit Werkszeugnis anlegen, Rüsten, Unterbrechen und Fertigmelden am Terminal, Korrektur durch den Meister, Zertifikatsfreigabe, Übernahme der Soll-Zeit und CSV-Export.

## Zeitzone

Laufende Buchungen rechnet die Datenbank mit `NOW()`, die App schreibt Zeitpunkte mit der Uhrzeit von PHP. Beide müssen dieselbe Zeitzone haben. Die App nutzt `Europe/Berlin` (`APP_TIMEZONE`), XAMPP die Uhrzeit von Windows, Docker setzt `TZ=Europe/Berlin`.
