# Technische Übergabe: Docker, Werker und Messung

Dieses Dokument gibt Teammitgliedern und ihren Entwicklungswerkzeugen einen Einstieg in den Stand des Pull Requests. Die Bedienung steht in der [Docker-Team-Anleitung](docker-team-guide.md) und der [Werker-Anleitung](werker-terminal.md).

## Einstieg

GitHub `main` bei `9445730a3a8c14d5bc9e42659d746db8acc4688d` ist die Grundlage dieses Branches. Der Branch enthält die Docker-Entwicklungsumgebung sowie die erweiterten Werker- und Zertifikatsabläufe. Das ursprüngliche Laravel-Projekt und seine SQL-Skripte bleiben die Grundlage. Git verwaltet Quellcode; jede Person hat eine eigene lokale Datenbank. Zugangsdaten in `.env`, Datenbankinhalte und lokale Volumes gehören nicht in Git.

Die Anwendung liegt unter `laravel/`: Laravel 13, Livewire 4 und Fortify; Docker stellt PHP 8.4/Apache, MariaDB 11.8, Composer 2 und Node 22 bereit. Composer und npm installieren anhand der Lockfiles. Der Source-Mount macht PHP- und Blade-Änderungen unmittelbar sichtbar, Frontend-Dateien werden mit Node gebaut. Die Projektrolle `node` läuft nur bei Bedarf.

Vor dem Merge: `git clone --branch chore/docker-development https://github.com/kusmitch2000-pixel/FA-Digital.git`. Danach im Ordner `laravel/` die Schritte in der Docker-Team-Anleitung ausführen. Standard-Testdaten und die beiden zusätzlichen Demo-Werker werden bewusst durch getrennte Seeder angelegt. Die Anmeldeseite zeigt Beispielpasswörter nur bei `APP_ENV=local`. Diese Beispiele sind nicht für einen öffentlichen oder produktiven Betrieb geeignet.

## Fachlicher Aufbau

- `app/Livewire/Terminal.php` und `resources/views/livewire/terminal.blade.php`: persönlicher Arbeitsvorrat, Zeitstempel, Unterbrechungen und Notizen. `app/Actions/Fertigung/Werkstatt.php` enthält die eigentlichen transaktionalen Abläufe und prüft die Zuordnung beim Start serverseitig.
- `app/Livewire/AuftragDetail.php`: Berechtigte ordnen Werker je Arbeitsgang zu; Rollen und Rechte stehen in `config/fa.php`. Bestehende Werker haben nicht automatisch neue Zuordnungen.
- `app/Livewire/Messung.php` und `resources/views/livewire/messung.blade.php`: Nach der Arbeit an einem Zertifikatsauftrag erfasst der ausführende Werker die hinterlegten Prüfpunkte auf einer eigenen Seite. Unvollständige Messungen bleiben im Status `messen`; der Werker kann sie aus dem Terminal wieder öffnen. Die Messzeit zählt derzeit nicht als Fertigungszeit.
- Nach vollständiger Erfassung wird der Arbeitsgang erledigt und nach dem letzten Arbeitsgang der Auftrag fertig. Ein Ergebnis „n. i. O.“ zählt als erfasst, blockiert aber die separate Zertifikatsfreigabe. **Offener Punkt:** Nach Abschluss gibt es noch keine Oberfläche, um einen fehlgeschlagenen Messwert zu korrigieren oder den Messvorgang erneut zu öffnen. Die hinterlegten Prüfpunkte allein sind kein Nachweis vollständiger Normkonformität.
- Die Datenbankbasis stammt aus `datenbank/01_schema.sql` und `03_sichten.sql`; Laravel-Migrationen ergänzen Zuordnung und Notizen und erweitern Statuswerte für `messen`. Die Migration für `messen` aktualisiert die Sichten und verweigert ein automatisches Rollback, weil vorhandene Messzustände verloren gehen könnten. Backups vor Schemaänderungen sind wichtig. Das SQL-Schema enthält `DROP DATABASE`: nicht direkt auf eine bestehende Datenbank ausführen.

## Prüfen und weiterarbeiten

Die Team-Anleitung beschreibt eine isolierte Testdatenbank. Die vorhandenen Feature-Tests decken Anmeldung, Rollenrechte, Fertigungsabläufe, Zuordnungen, Unterbrechungen und Messungen ab. Testdaten nur in der separaten Testumgebung zurücksetzen, niemals versehentlich in der Entwicklungsdatenbank. Der letzte Testlauf auf dem Apple-Silicon-Mac ergab 38 erfolgreiche Tests und 335 Assertions; Plattformen Windows/WSL2, Linux und Intel-Mac wurden dabei nicht geprüft.

`npm ci` meldete beim damaligen Test 11 Sicherheitswarnungen in den vorhandenen Node-Abhängigkeiten; `npm audit` erneut ausführen und Updates separat bewerten. Der Frontend-Build meldete eine optionale Font-Fallback-Warnung, lief aber durch. Versions-Tags der Docker-Images sind nicht auf Digests festgeschrieben.

Vor Änderungen die vorhandenen Dokumente und den aktuellen Git-Status lesen. Aufträge, Arbeitsgänge, Zertifikate und Prüfmerkmale hängen zusammen; neue Funktionen sollten Rollenrechte, Zustand und Mehrschritt-Aufträge mit Tests prüfen. Bestehende Daten nicht ungefragt importieren oder löschen; XAMPP-Daten werden für diese Migration nicht benötigt.
