# Docker-Migration

## Ausgangslage und Ziel

Grundlage ist GitHub-`main` bei `9445730a3a8c14d5bc9e42659d746db8acc4688d`. Die lokale Referenz liegt unter `~/Desktop/Master Studium/Semester 4/FA-Digital`, Branch `laravel`, Commit `f9b9eaf`; sie wurde nicht verändert. Der lokale Stand enthält eine ältere Laravel-Anwendung und ein anderes Datenmodell. Der GitHub-Stand enthält die neuere Anwendung und bereits eine Weiterentwicklung der lokalen Docker-Konfiguration. Der Git-Status der Referenz war sauber; ignorierte lokale Dateien wurden nicht überschrieben.

Übernommen bzw. beibehalten wurden das PHP-8.4-Apache-Image, Composer, die Apache-Regel für `public/`, MariaDB 11.8 mit Healthcheck und Volume, sowie das Node-22-Konzept zum Bauen der Vite-Dateien. Angepasst wurden Quellcode-Mount, Entwicklungsabhängigkeiten, Konfiguration und Startskript. Migration und Seeder laufen ausdrücklich per Kommando statt automatisch beim Start. Die SQL-Skripte aus `datenbank/` werden lesbar eingebunden: die GitHub-Migrationen benötigen sie. Ein zusätzliches Redis, Queue-Worker oder Scheduler ist nicht erforderlich; die lokale Queue läuft synchron.

## Aufbau und Entscheidungen

- `app`: PHP 8.4, Apache, Laravel 13, Livewire 4 und Composer 2. Der Checkout ist eingebunden, Änderungen an PHP und Blade sind direkt sichtbar. Composer-Abhängigkeiten liegen im Volume `vendor`.
- `db`: MariaDB 11.8, nur im internen Compose-Netz erreichbar. `db-data` bewahrt die Daten beim Stoppen auf. App und Datenbank nutzen Europe/Berlin, damit Zeitberechnungen zusammenpassen.
- `node`: nur bei Bedarf gestartetes Werkzeug mit Node 22. `npm ci` nutzt `package-lock.json`, `npm run build` erzeugt `public/build`; `node_modules` liegt im eigenen Volume. Nach Änderungen an Frontend-Quellen erneut bauen.

Docker bündelt PHP, Erweiterungen, Webserver, Node und Datenbank in reproduzierbareren Umgebungen. Es vermeidet lokale XAMPP- und Versionskonflikte und erleichtert Onboarding. Images und Volumes benötigen Speicher, Docker Desktop Arbeitsspeicher; Compose und Container verlangen Einarbeitung. Versions-Tags erhalten Updates, sind also nicht bitgenau unveränderlich. Git und GitHub versionieren den Quellcode; Datenbanken und `.env` werden nicht zwischen Teammitgliedern synchronisiert. Netzwerkzugang ist beim ersten Laden und Installieren nötig.

## Daten und Rückkehr

Quellcode, Schema und Daten sind getrennt: Migrationen verwenden `datenbank/01_schema.sql` und `03_sichten.sql`; der optionale Seeder verwendet `02_testdaten.sql`. Das Schema-SQL enthält selbst ein `DROP DATABASE`: niemals direkt auf eine bestehende Datenbank ausführen. Laravel überspringt diese Datenbank-Anweisungen beim Einlesen. Ein vorhandener XAMPP-Bestand wurde weder geprüft noch importiert; laut Rückmeldung genügt eine neue Entwicklungsdatenbank. Für eine spätere Datenübernahme gilt: das andere Schema des alten lokalen Stands ist nicht automatisch kompatibel. Vor einem Import Backup erstellen, Version, Tabellen, Sichten und Inhalte vergleichen und zunächst eine isolierte Kopie testen (siehe Team-Anleitung). Keine produktiven Daten ohne Freigabe übertragen.

Der ursprüngliche Ordner und dessen Git-Stand bleiben erhalten; zur Rückkehr dort arbeiten. Der separate Klon und seine eigenen Volumes verändern ihn nicht. `docker compose down` stoppt Container ohne Volumes zu löschen. Ein Volume zu löschen ist ein eigenständiger Datenverlust und wurde nicht durchgeführt.
