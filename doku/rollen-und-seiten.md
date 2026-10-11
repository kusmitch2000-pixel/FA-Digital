# Rollen und Seiten

Wer welche Seite von FA-Digital sieht, was davon heute schon geht und was noch fehlt. Stand: 11.10.2026.

## Stand heute

- Der Prototyp kennt keine Rollen. „Anmelden“ führt ohne Prüfung zur Hallenansicht.
- Auf allen Seiten steht oben „Sven Petersen · Admin“, und das Menü zeigt alle Punkte. Man sieht also immer die Admin-Perspektive.
- Die Datenbank ist vorbereitet: `benutzer.rolle` mit fünf Rollen und Test-Benutzer für jede Rolle (Liste in `datenbank/README.md`, Passwort `fa-digital`). Katrin Wagner ist als inaktiver Werker angelegt und darf sich später nicht anmelden. Die Website fragt die Datenbank aber noch nicht ab.
- Vorgabe aus dem Kurzkonzept: „Für alle Seiten gilt eine Anmeldung mit E-Mail und Passwort. Jede Rolle sieht nur ihre Menüpunkte.“ Als nächsten Schritt nennt es das PHP-Grundgerüst mit Login und Rollen.

## Wer sieht was (Vorschlag)

Abgeleitet aus dem Kurzkonzept (Rollen und Anwendungsfälle) und den 12 Anwendungsfällen im Fachkonzept (AF).

| Rolle | Menüpunkte | Grundlage |
|---|---|---|
| Werker | Werker-Terminal, Aufträge, Hallenansicht | AF 1–5, 8 |
| Meister | Hallenansicht, Aufträge; im Auftragsdetail Buchungen korrigieren, Auftrag abschließen, Werkszertifikat | AF 6–9 |
| Controlling | Nachkalkulation, Auswertung, Arbeitsplätze (Stundensätze), Aufträge, Hallenansicht | AF 8, 10, 11; Kurzkonzept „pflegt Stundensätze“ |
| Arbeitsvorbereitung | Auftrag anlegen, Artikel & Arbeitspläne, Arbeitsplätze, Aufträge, Hallenansicht | nur Kurzkonzept, im Fachkonzept ohne AF |
| Admin | alle | AF 12; Kurzkonzept „hat Zugriff auf alle Bereiche“ |

Startseite nach der Anmeldung: Werker landen direkt im Werker-Terminal, alle anderen in der Hallenansicht.

## Offene Punkte fürs Team

1. **Hallenansicht:** Im Kurzkonzept ist sie für alle Rollen da, im Fachkonzept (AF 7) nur für den Meister. Sollen Werker und Controlling sie sehen?
2. **Arbeitsvorbereitung:** Die Rolle steht im Fachkonzept, hat dort aber keinen Anwendungsfall mehr, weil die Stammdatenpflege zurückgestellt ist. Bleiben „Auftrag anlegen“, „Artikel & Arbeitspläne“ und „Arbeitsplätze“ im Menü? Wenn nicht, wer legt die Aufträge an?

## Zwei Wege zu den Rollen-Perspektiven

### A) Rollenwechsel im Prototyp (JavaScript)

Für die Online-Version auf GitHub Pages ist das der einzige Weg, weil dort kein PHP läuft.

- Auf der Anmeldeseite einen Test-Benutzer wählen, die E-Mail bestimmt die Rolle
- `prototyp.js` merkt sich die Rolle im Browser, zeigt oben Name und Rolle, blendet fremde Menüpunkte aus und öffnet die Startseite der Rolle
- Nur Darstellung: Wer die Adresse einer Seite kennt, kommt trotzdem hin. Für einen Prototyp reicht das
- Fällt mit PHP weg. Die Zuordnung „Rolle → Seiten“ oben bleibt gültig

### B) Echte Anmeldung mit PHP (geplanter nächster Schritt)

- Die Anmeldung lädt den Benutzer zur E-Mail. Die Abfrage steht schon in `datenbank/04_abfragen.sql` und berücksichtigt nur aktive Benutzer. Das Passwort prüft `password_verify()`
- Nach der Anmeldung stehen Benutzer-ID und Rolle in der Session
- Ein gemeinsames Menü für alle Seiten zeigt nur die erlaubten Punkte
- Jede Seite prüft am Anfang die Rolle und schickt sonst zur Startseite zurück. Erst das schützt die Seiten wirklich, Ausblenden im Menü allein reicht nicht
- Perspektiven testen: abmelden und als anderer Test-Benutzer anmelden. Zwei Rollen gleichzeitig gehen in zwei Browsern oder mit einem privaten Fenster

## Branch `laravel`

Dort gibt es schon eine echte Anmeldung, aber nur mit den Rollen `admin` und `werker`. Mehrere Funktionen prüfen nur, ob jemand `admin` ist. Das Modell mit fünf Rollen ist dort nicht umgesetzt.
