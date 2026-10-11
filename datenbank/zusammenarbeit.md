# Datenbank im Team

Wie alle in der Gruppe mit denselben Datensätzen arbeiten.

## Grundprinzip

Die Datenbank läuft bei jedem lokal in XAMPP auf dem eigenen Rechner. Niemand greift auf die Datenbank eines anderen zu. Geteilt werden nicht die Datensätze selbst, sondern die SQL-Skripte im Repository. Wer 01, 02 und 03 importiert, hat dieselbe Datenbank wie alle anderen.

```
GitHub-Repository: datenbank/01–03
        │  git pull + importieren
        ├── Laptop A: XAMPP → fa_digital
        ├── Laptop B: XAMPP → fa_digital
        └── Laptop C: XAMPP → fa_digital
```

Gleich sind Tabellen, Datensätze und IDs. Nur die Zeitpunkte unterscheiden sich, weil die Testdaten ab dem Importzeitpunkt rechnen.

## Neu in der Gruppe (einmalig)

1. Einladung zum Repository annehmen und klonen (`README.md` im Hauptordner)
2. XAMPP installieren
3. 01, 02 und 03 importieren, Schritt für Schritt in `anleitung-phpmyadmin.md`

## Auf den neuesten Stand bringen

Wenn jemand die Skripte geändert hat:

1. `git pull`
2. 01, 02 und 03 neu importieren, immer alle drei in dieser Reihenfolge

02 allein noch einmal einzuspielen scheitert an doppelten Einträgen (`#1062 Duplicate entry`). 01 löscht die alte Datenbank vorher.

## Regeln für Änderungen

| Was | Wo ändern | Worauf achten |
|---|---|---|
| Tabellen, Spalten, Prüfregeln | `01_schema.sql` | Passen Sichten und Testdaten noch? Datenmodell-Dokument und ER-Diagramm in `doku/` nachziehen |
| Testdaten | `hilfsskripte/erzeuge-testdaten.js`, dann `node erzeuge-testdaten.js` | Beide Dateien committen. Node.js ist nicht in XAMPP enthalten |
| Sichten | `03_sichten.sql` | |
| Beispielabfragen und -abläufe | `04_abfragen.sql`, `05_beispielablaeufe.sql` | |

Danach committen, pushen und in der Gruppe Bescheid geben. Die anderen holen den Stand mit `git pull` und importieren neu.

- **Nicht nur in phpMyAdmin ändern.** Was man dort anklickt oder eintippt, bleibt auf dem eigenen Rechner und ist beim nächsten Import von 01 weg.
- **`02_testdaten.sql` nicht von Hand bearbeiten.** Die Datei wird aus `erzeuge-testdaten.js` erzeugt, der nächste Lauf überschreibt Handänderungen.
- **Schema nur nach Absprache ändern.** Ändern zwei Leute gleichzeitig `01_schema.sql`, kann es beim Zusammenführen Konflikte geben. Vorher in der Gruppe sagen, wer gerade daran arbeitet.

## Später mit PHP

Jede PHP-Seite verbindet sich mit der Datenbank auf dem eigenen Rechner: Host `localhost`, Benutzer `root`, kein Passwort, Datenbank `fa_digital`. Weicht das bei jemandem ab, z. B. anderer Port oder ein gesetztes Passwort, gehören die Zugangsdaten in eine eigene Konfigurationsdatei, die nicht ins Repository kommt.

## Eine gemeinsame Datenbank für alle?

Für die Entwicklung nicht nötig. Eine zentrale Datenbank braucht einen Server, der immer läuft. Ein Fehler von einer Person stört dann alle, und ohne Internet geht nichts. Über das Netzwerk auf das XAMPP eines anderen Laptops zuzugreifen geht zwar, aber dieser Laptop muss laufen und im selben Netz sein, und `root` hat bei XAMPP kein Passwort.

Wichtig wird das erst für die Vorführung, wenn die fertige Anwendung online laufen soll. GitHub Pages reicht dann nicht mehr, weil es weder PHP noch eine Datenbank ausführt. Bis dahin läuft die Vorführung auf einem Laptop mit XAMPP.

**Offen:** Stellt die HAW für die Vorführung einen Server mit PHP und MariaDB? Bei der Betreuung nachfragen.

## Hinweis zum Branch `laravel`

Der Branch `laravel` (27.09.2026) ist ein früherer Ansatz mit Laravel und Docker. Dort entsteht die Datenbank nach demselben Prinzip aus Migrationen und Seedern, aber mit einem eigenen Datenmodell. Die Skripte aus `datenbank/` nutzt er nicht. Wer dort arbeitet, sieht also nicht dieselben Datensätze.
