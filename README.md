# FA-Digital

Digitaler Fertigungsauftrag mit Zeiterfassung und Nachkalkulation. Projekt im Projektseminar Softwareentwicklung (HAW Kiel, WiSe 2026/27).

**Schwerpunkt:** FA-Digital deckt ab, was während und nach der Produktion passiert. Werker buchen Zeiten, am Ende stehen Soll-Ist-Vergleich und Kosten.

## Docker-Schnellstart

Eine kurze Erklärung der Änderungen und des Wechsels von XAMPP zu Docker steht in [Docker-Änderungen kurz erklärt](docs/docker-aenderungen-kurz.md).

Nach Installation und Start von Docker mit Compose v2:

```sh
cd laravel
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install --no-interaction
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose run --rm node ci
docker compose run --rm node run build
```

Anschließend <http://localhost:8000> öffnen. Der Seeder lädt optionale Entwicklungsdaten. Eine [technische Übergabe für die Weiterentwicklung](docs/entwickler-uebergabe.md) beschreibt Aufbau, Entscheidungen und offene Punkte. Details, Zugangsdaten, sichere Tests und tägliche Befehle stehen in der [Docker-Team-Anleitung](docs/docker-team-guide.md); Hintergründe und Datenmigration in [Docker-Migration](docs/docker-migration.md).

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

Der statische Prototyp und die Laravel-App können sich bei Rollen und Funktionen unterscheiden; maßgeblich für die App ist `laravel/config/fa.php`. Ruft eine Rolle die Adresse einer fremden Seite auf, zeigt der Prototyp „Kein Zugriff“. Ohne Anmeldung, z. B. über einen direkten Link, gilt der Admin.

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
- **Anwendung** (`laravel/`): PHP mit Laravel 13, Livewire 4 für die Seiten und Fortify für die Anmeldung. Die Migrationen führen die SQL-Skripte aus `datenbank/` aus, weitere Migrationen ergänzen Zuordnungen, Notizen und Statuswerte. Start und Einrichtung mit Docker stehen oben

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
