# FA-Digital: Was wurde für Docker geändert?

## Warum der Wechsel?

Das Team soll FA-Digital auf verschiedenen Rechnern möglichst gleich einrichten und entwickeln können. Mit XAMPP und separat installierten Werkzeugen können unterschiedliche PHP- oder Datenbankversionen zu Problemen führen. Docker stellt die benötigten Werkzeuge in einer gemeinsamen Konfiguration bereit. Jedes Teammitglied startet damit seine eigene lokale Umgebung und Datenbank – ohne XAMPP oder eine separate PHP- und Datenbankinstallation.

Docker ersetzt **nicht** Git und GitHub: Sie verwalten weiterhin den gemeinsamen Quellcode. Die lokalen Datenbanken werden nicht automatisch zwischen Teammitgliedern geteilt.

## Was wurde geändert?

- **GitHub als Grundlage:** Die aktuelle Anwendung aus dem GitHub-Branch `main` bleibt erhalten. Der ältere lokale Projektordner wurde nur als Vorlage für Docker verwendet und nicht überschrieben.
- **Docker-Konfiguration angepasst:** Die Anwendung läuft mit PHP und Apache; eine zweite Komponente stellt MariaDB bereit. Node wird bei Bedarf zum Erstellen der Frontend-Dateien verwendet. Die Komponenten kommunizieren innerhalb von Docker.
- **Entwicklung vereinfacht:** Änderungen am Quellcode im Projektordner sind für den Anwendungscontainer sichtbar. Die Datenbank bleibt beim normalen Stoppen und Neustarten erhalten.
- **Daten bewusst getrennt:** Eine neue lokale Datenbank wird mit den vorhandenen Migrationen angelegt. Beispieldaten können zusätzlich geladen werden. Vorhandene XAMPP-Daten wurden auf deinen Wunsch nicht übernommen.
- **Anleitung ergänzt:** Die [Docker-Team-Anleitung](docker-team-guide.md) beschreibt die Einrichtung und täglichen Befehle. Technische Hintergründe stehen in [Docker-Migration](docker-migration.md).

## Was bedeutet das für das Team?

Docker Desktop beziehungsweise Docker mit Compose muss installiert und gestartet sein. Anschließend folgt jedes Teammitglied einmal der Einrichtungsanleitung und arbeitet mit seiner eigenen Datenbank. Docker benötigt zusätzlichen Speicher und etwas Einarbeitung, erleichtert aber eine einheitliche Einrichtung.

Die Änderungen liegen momentan nur lokal auf dem Branch `chore/docker-development`. Sie müssen noch vom Team geprüft und in den gemeinsamen GitHub-Stand übernommen werden. Der Test auf dem Mac war erfolgreich: Anwendung und Datenbank starteten, und alle 33 vorhandenen Anwendungstests bestanden.
