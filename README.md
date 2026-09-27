# FA-Digital

Digitaler Fertigungsauftrag mit Zeiterfassung und Nachkalkulation. Projekt im Projektseminar Softwareentwicklung (HAW Kiel, WiSe 2026/27).

## Ordner

| Ordner | Inhalt |
|---|---|
| `prototyp/` | Klickbarer HTML-Prototyp, 11 Seiten und `style.css`, noch ohne Datenbank |
| `doku/` | Kurzkonzept, KI-Prompt-Protokoll, später Fachkonzept, DV-Konzept und KI-Konzept |

## Online ansehen und Feedback geben

- **Website:** https://kusmitch2000-pixel.github.io/FA-Digital/ (wird bei jedem Push automatisch aktualisiert)
- **Feedback:** auf der Startseite „Feedback geben“ klicken. Das öffnet ein GitHub-Issue mit Vorlage. Pro Punkt ein eigenes Issue
- **Alle Rückmeldungen:** https://github.com/kusmitch2000-pixel/FA-Digital/issues?q=label%3Afeedback

## Prototyp lokal öffnen

`prototyp/index.html` doppelt anklicken. Die Anmeldung ist noch nicht geprüft, „Anmelden“ führt direkt zum Dashboard.

`prototyp/prototyp.js` gibt es nur, solange die Seiten statisch sind. Es leitet abgesendete Formulare direkt auf die Zielseite weiter, weil GitHub Pages keine POST-Anfragen annimmt. Mit PHP fällt die Datei weg.

## Seiten

| Datei | Seite | Anwendungsfälle |
|---|---|---|
| `index.html` | Anmeldung | Login |
| `dashboard.html` | Dashboard | 10 |
| `auftraege.html` | Auftragsübersicht | 9 |
| `auftrag-anlegen.html` | Neuer Fertigungsauftrag | 3, 4 |
| `auftrag-detail.html` | Auftragsdetail FA-1002 | 4, 8, 11 |
| `werker.html` | Werker-Terminal | 5, 6, 7 |
| `nachkalkulation.html` | Nachkalkulation FA-1001 | 12 |
| `auswertung.html` | Auswertung und Export | 13 |
| `artikel.html` | Artikel und Arbeitspläne | 1 |
| `arbeitsplaetze.html` | Arbeitsplätze und Stundensätze | 2 |
| `benutzer.html` | Benutzerverwaltung | 14 |

## Technik

HTML und CSS. Später PHP mit PDO und MariaDB über XAMPP.

## Zusammenarbeit im Team

Jeder arbeitet mit seinem eigenen GitHub-Konto. So zeigt die Commit-Historie, wer welchen Teil gebaut hat. Das ist für die Bewertung wichtig.

Einmalig das Repository klonen, z. B. in VS Code über „Git: Clone“ oder im Terminal:

```
git clone https://github.com/kusmitch2000-pixel/FA-Digital.git
```

Das Repository ist privat. Vorher die Einladung auf GitHub annehmen (E-Mail oder https://github.com/kusmitch2000-pixel/FA-Digital/invitations).

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
