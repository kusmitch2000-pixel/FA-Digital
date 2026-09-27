# FA-Digital

Digitaler Fertigungsauftrag mit Zeiterfassung und Nachkalkulation. Projekt im Projektseminar Softwareentwicklung (HAW Kiel, WiSe 2026/27).

**Schwerpunkt:** FA-Digital deckt ab, was während und nach der Produktion passiert. Werker buchen Zeiten, am Ende stehen Soll-Ist-Vergleich und Kosten.

## Ordner

| Ordner | Inhalt |
|---|---|
| `prototyp/` | Klickbarer HTML-Prototyp, 11 Seiten und `style.css`, noch ohne Datenbank |
| `doku/` | **Fachkonzept** (`Fachkonzept_FA-Digital.docx` und `.pdf`), Kurzkonzept, Prompt-Protokoll, später DV-Konzept und KI-Konzept |
| `doku/prozesse/` | Prozessdiagramme für das Fachkonzept: `auftrag`, `checkliste`, `verbesserung`. `prozesse.js` erzeugt die HTML-Seiten, daraus entstehen die PNG-Bilder. Die Stammdatenpflege liegt vorerst in `zurueckgestellt/` |
| `doku/fachkonzept/` | Inhalt des Fachkonzepts als `inhalt.json` und die Word-Skripte, die daraus das Dokument bauen |

## Online ansehen und Feedback geben

- **Website:** https://kusmitch2000-pixel.github.io/FA-Digital/ (wird bei jedem Push automatisch aktualisiert)
- **Feedback:** auf der Startseite „Feedback geben“ klicken. Das öffnet ein GitHub-Issue mit Vorlage. Pro Punkt ein eigenes Issue
- **Alle Rückmeldungen:** https://github.com/kusmitch2000-pixel/FA-Digital/issues?q=label%3Afeedback

## Prototyp lokal öffnen

`prototyp/index.html` doppelt anklicken. Die Anmeldung ist noch nicht geprüft, „Anmelden“ führt direkt zur Hallenansicht.

`prototyp/prototyp.js` gibt es nur, solange die Seiten statisch sind. Es leitet abgesendete Formulare direkt auf die Zielseite weiter, weil GitHub Pages keine POST-Anfragen annimmt. Mit PHP fällt die Datei weg.

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

HTML und CSS. Später PHP mit PDO und MariaDB über XAMPP.

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
