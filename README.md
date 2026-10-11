# FA-Digital

Digitaler Fertigungsauftrag mit Zeiterfassung und Nachkalkulation. Projekt im Projektseminar Softwareentwicklung (HAW Kiel, WiSe 2026/27).

**Schwerpunkt:** FA-Digital deckt ab, was während und nach der Produktion passiert. Werker buchen Zeiten, am Ende stehen Soll-Ist-Vergleich und Kosten.

## Ordner

| Ordner | Inhalt |
|---|---|
| `prototyp/` | Klickbarer HTML-Prototyp, 11 Seiten und `style.css`, noch ohne Datenbank |
| `laravel/` | **Anwendung**: Laravel 13 mit Livewire und Docker, arbeitet direkt mit dem Datenmodell aus `datenbank/`. Alle 12 Anwendungsfälle, eine Ansicht je Rolle. Einrichtung in `laravel/README.md` |
| `datenbank/` | **Datenmodell als SQL**: Schema, Testdaten, Sichten, Beispielabfragen und -abläufe für MariaDB (XAMPP). Anleitung in `datenbank/README.md` |
| `doku/` | **Fachkonzept** (`Fachkonzept_FA-Digital.docx` und `.pdf`), **Datenmodell** (`Datenmodell_FA-Digital.docx` und `.pdf`), Kurzkonzept, Prompt-Protokoll, später DV-Konzept und KI-Konzept |
| `doku/prozesse/` | Prozessdiagramme für das Fachkonzept: `auftrag`, `checkliste`, `verbesserung`. `prozesse.js` erzeugt die HTML-Seiten, daraus entstehen die PNG-Bilder. Die Stammdatenpflege liegt vorerst in `zurueckgestellt/` |
| `doku/datenmodell/` | ER-Diagramm des Datenmodells |

## Online ansehen und Feedback geben

- **Website:** https://kusmitch2000-pixel.github.io/FA-Digital/ (wird bei jedem Push automatisch aktualisiert)
- **Feedback:** auf der Startseite „Feedback geben“ klicken. Das öffnet ein GitHub-Issue mit Vorlage. Pro Punkt ein eigenes Issue
- **Alle Rückmeldungen:** https://github.com/kusmitch2000-pixel/FA-Digital/issues?q=label%3Afeedback

## Prototyp lokal öffnen

`prototyp/index.html` doppelt anklicken und einen Test-Benutzer wählen. Die E-Mail bestimmt die Rolle, das Passwort wird nicht geprüft.

| Rolle | Test-Benutzer | Startseite | Menü |
|---|---|---|---|
| Werker | Jonas Wolff | Werker-Terminal | Werker-Terminal, Aufträge |
| Meister | Lena Hoffmann | Hallenansicht | Hallenansicht, Aufträge, Werker-Terminal, Nachkalkulation, Auswertung. Im Auftragsdetail Buchungen nachtragen |
| Arbeitsvorbereitung | Anna Becker | Aufträge | Hallenansicht, Aufträge, Auftrag anlegen, Artikel & Arbeitspläne, Arbeitsplätze |
| Controlling | Tobias Krüger | Nachkalkulation | Hallenansicht, Aufträge, Nachkalkulation (abschließen), Auswertung, Arbeitsplätze |
| Admin | Sven Petersen | Hallenansicht | alles |

Die Zuordnung ist dieselbe wie in der Laravel-App (`laravel/config/fa.php`). Ruft eine Rolle die Adresse einer fremden Seite auf, zeigt der Prototyp „Kein Zugriff“. Ohne Anmeldung, z. B. über einen direkten Link, gilt der Admin.

`prototyp/prototyp.js` gibt es nur, solange die Seiten statisch sind. Es merkt sich den Test-Benutzer im Browser, blendet fremde Menüpunkte und Aktionen aus und leitet abgesendete Formulare direkt auf die Zielseite weiter, weil GitHub Pages keine POST-Anfragen annimmt. Das ist nur Darstellung. Die echte Anmeldung mit Passwort und Rechteprüfung macht die Laravel-App.

## Seiten

| Datei | Seite | Anwendungsfälle |
|---|---|---|
| `index.html` | Anmeldung | Login |
| `dashboard.html` | Hallenansicht: eine Karte je Arbeitsplatz mit Ampel | 12 |
| `auftraege.html` | Auftragsübersicht | 13 |
| `auftrag-anlegen.html` | Neuer Fertigungsauftrag | 4, 5 |
| `auftrag-detail.html` | Auftragsdetail FA-1002 | 5, 11, 14 |
| `werker.html` | Werker-Terminal mit Unterbrechungsgrund | 6, 7, 8, 10 |
| `nachkalkulation.html` | Nachkalkulation FA-1001 | 16 |
| `auswertung.html` | Auswertung, Unterbrechungen nach Grund, CSV-Export | 17 |
| `artikel.html` | Artikel, Arbeitspläne, Soll-Zeit-Vorschläge | 1, 2 |
| `arbeitsplaetze.html` | Arbeitsplätze und Stundensätze | 3 |
| `benutzer.html` | Benutzerverwaltung | 18 |

Die Nummern beziehen sich auf die 18 Anwendungsfälle im Fachkonzept (`doku/Fachkonzept_FA-Digital.pdf`). Anwendungsfall 9 (Checkliste im Arbeitsgang) und 15 (Werkszertifikat) sind im Prototyp noch nicht umgesetzt.

## Technik

- **Prototyp** (`prototyp/`): HTML und CSS, ohne Datenbank, online über GitHub Pages
- **Datenmodell** (`datenbank/`): MariaDB mit 14 Tabellen und 14 Sichten. Die Sichten rechnen Soll-/Ist-Zeiten, Kosten, Ampeln und Vorschläge
- **Anwendung** (`laravel/`): PHP mit Laravel 13, Livewire 4 für die Seiten und Fortify für die Anmeldung. Die Migrationen führen die SQL-Skripte aus `datenbank/` aus, es gibt das Datenmodell also nur einmal. Start mit Docker (`docker compose up`) oder mit PHP 8.4 und der MariaDB aus XAMPP

## Zusammenarbeit im Team

Jeder arbeitet mit seinem eigenen GitHub-Konto. So zeigt die Commit-Historie, wer welchen Teil gebaut hat. Das ist für die Bewertung wichtig.

Einmalig das Repository klonen, z. B. in VS Code über „Git: Clone“ oder im Terminal:

```
git clone https://github.com/kusmitch2000-pixel/FA-Digital.git
```

Zum Hochladen von Änderungen vorher die Einladung auf GitHub annehmen (E-Mail oder https://github.com/kusmitch2000-pixel/FA-Digital/invitations).

Bei jeder Arbeitssitzung:

1. Vorher den neuesten Stand holen: `git pull`
2. Arbeiten
3. Änderungen speichern und hochladen:
   ```
   git add .
   git commit -m "Kurz beschreiben, was geändert wurde"
   git push
   ```

Prompts an die KI tragt ihr mit eurem Namen in `doku/KI-Konzept_Prompt-Protokoll.md` ein.
