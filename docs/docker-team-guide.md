# Docker-Anleitung für das Team

## Voraussetzungen

Git und Docker mit Compose v2; Internet für Images und Abhängigkeiten. macOS: Docker Desktop für Apple Silicon oder Intel installieren und starten. Windows: Docker Desktop mit WSL2-Integration; Repository am besten im Linux-Dateisystem der WSL-Distribution klonen und dort im Terminal arbeiten. Linux: Docker Engine und Compose-Plugin installieren, Dienst starten und Zugriff auf Docker einrichten. PHP, Composer, Node und MariaDB müssen nicht auf dem Rechner installiert sein. Auf dem Mac kann der Docker-Client gegebenenfalls unter `/Applications/Docker.app/Contents/Resources/bin/docker` liegen.

## Einmalige Einrichtung

Im Terminal:

```sh
git clone --branch chore/docker-development https://github.com/kusmitch2000-pixel/FA-Digital.git
cd FA-Digital/laravel
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install --no-interaction
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose run --rm node ci
docker compose run --rm node run build
```

Vor dem Merge lädt der Klonbefehl den Branch dieses Pull Requests. Nach dem Merge kann das Team stattdessen den Standardbranch `main` klonen. `.env` enthält lokale Entwicklungsbeispiele und den privaten Anwendungsschlüssel; nicht committen oder weitergeben. `db:seed` ist optional und für eine neue Entwicklungsdatenbank gedacht. Er lädt Beispieldaten nur, wenn `benutzer` leer ist; er ist kein Importverfahren für echte Daten. Beispiel-Admin: `sven.petersen@fa-digital.de`, Passwort `fa-digital`. Alternativ nach der Migration ohne Seeder `docker compose exec app php artisan fa:admin name@example.org` ausführen.

Anwendung: <http://localhost:8000>. `APP_PORT` und `APP_URL` in `.env` gemeinsam ändern, wenn 8000 belegt ist; danach `docker compose up -d` ausführen. Die Bindung an `127.0.0.1` begrenzt den Zugriff auf den eigenen Rechner. Die Datenbank hat keinen veröffentlichten Host-Port.

## Täglich arbeiten

```sh
docker compose up -d
docker compose ps
docker compose logs -f app
docker compose logs -f db
docker compose stop
docker compose start
```

`docker compose down` entfernt Container und Netzwerk, bewahrt aber Volumes. Beim nächsten `up -d` werden Container neu erstellt. PHP- und Blade-Änderungen sind direkt sichtbar. Nach Änderungen an CSS/JS `docker compose run --rm node run build` wiederholen; bei geänderten Node-Abhängigkeiten vorher `docker compose run --rm node ci`. Es wird hier kein dauerhaft laufender Vite-Server benötigt. `npm ci` meldete beim Test 11 Sicherheitswarnungen (4 mittel, 2 hoch, 5 kritisch); das Team sollte `docker compose run --rm node audit` prüfen und Aktualisierungen gesondert bewerten. Artisan und Composer laufen im Container, z. B. `docker compose exec app php artisan migrate:status`.

Nach `git pull` im richtigen Branch: bei Dockerfile- oder Compose-Änderungen `docker compose up -d --build`, bei geänderter `composer.lock` `docker compose exec app composer install --no-interaction`, bei neuer Migration `docker compose exec app php artisan migrate`; Node-Befehle wie oben. Vorher eigene Änderungen und Daten sichern. Ein geändertes Datenbankpasswort in `.env` ändert **nicht** automatisch den Benutzer eines bereits initialisierten MariaDB-Volumes.

## Tests

Tests können Tabellen zurücksetzen. Eine separate Compose-Projektkennung und eine eigene Datenbank verwenden, niemals die Entwicklungsdatenbank:

```sh
DB_DATABASE=fa_digital_test APP_PORT=8001 docker compose -p fa-digital-tests up -d --build
DB_DATABASE=fa_digital_test APP_PORT=8001 docker compose -p fa-digital-tests exec -e DB_DATABASE=fa_digital_test app php artisan test
DB_DATABASE=fa_digital_test APP_PORT=8001 docker compose -p fa-digital-tests down
```

Das Testprojekt erhält eigene Volumes; sein Datenbestand bleibt nach `down` bestehen. Das Testkommando verändert ausschließlich die dort konfigurierte Testdatenbank. Tests benötigen MariaDB wegen Sichten und SQL-Regeln. Das Testprojekt verwendet Port 8001. Falls dieser belegt ist, in allen drei Befehlen denselben freien Port eintragen.

## Backup und Import

Ein Beispiel für einen lokalen SQL-Export (Datei nicht committen):

```sh
docker compose exec -T db sh -c 'exec mariadb-dump -u "$MARIADB_USER" -p"$MARIADB_PASSWORD" --single-transaction --routines --triggers "$MARIADB_DATABASE"' > ~/backup-fa-digital.sql
```

Die Datei und `.env` sicher aufbewahren; zum Wiederherstellen wird derselbe `APP_KEY` benötigt, wenn verschlüsselte Anwendungsdaten verwendet werden. Für XAMPP zunächst dessen Datenbank mit phpMyAdmin oder `mariadb-dump` exportieren, ohne die Quelle zu verändern. Schema, MariaDB-Version (XAMPP-Dokumentation nennt 10.4), Zeichensatz, Sichten, DEFINER und Tabellen mit dem aktuellen Modell vergleichen. Ein Dump kann `DROP`, `CREATE DATABASE` oder `USE` enthalten: vor Import prüfen. Erst in einer separaten leeren Datenbank testen und Backup verifizieren. Nur einen geprüften, für die Zieldatenbank geeigneten Dump importieren:

```sh
docker compose exec -T db sh -c 'exec mariadb -u "$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' < gepruefter-dump.sql
```

**Achtung:** Ein Import kann vorhandene Daten überschreiben oder löschen. Vorher Ziel und Backup prüfen; nicht ungeprüft in die Entwicklungsdatenbank importieren. Bei abweichendem älterem Schema ist eine gesonderte Datenmigration erforderlich.

## Probleme

- Port belegt: `APP_PORT` und `APP_URL` ändern, Container neu erstellen.
- Datenbank nicht bereit: `docker compose ps` und `docker compose logs db`; `DB_HOST` muss `db` sein. Zugangsdaten und das bereits initialisierte Volume beachten.
- Fehlende Assets: Node-Build erneut ausführen; Browser neu laden.
- Schreibrechte: `storage` und `bootstrap/cache` müssen für Apache beschreibbar sein. Das Startskript richtet sie ein. Auf Linux können Containerbefehle Dateien mit fremdem Eigentümer erzeugen; Eigentümer im eigenen Checkout bei Bedarf korrigieren.
- `docker compose down -v`, `php artisan migrate:fresh`, `migrate:reset` und das direkte SQL-Schema können Daten löschen. Nicht ohne bewusstes Backup und ausdrückliche Entscheidung verwenden.
