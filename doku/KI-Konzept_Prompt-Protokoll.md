# KI-Konzept: Prompt-Protokoll

Dieses Protokoll hält fest, welche Prompts wir an das Sprachmodell gestellt haben und was dabei herausgekommen ist. Es ist die Grundlage für das KI-Konzept (10 % der Note).

- **Werkzeug:** Claude Code (Desktop-App), Modell Claude Opus 5.5
- **Vorgehen:** Schritt für Schritt. Wir geben Ziel und Rahmen vor, die KI schlägt vor und setzt um, wir prüfen und entscheiden.

---

## 1 · 27.09.2026 · Projektstart und fachlicher Zuschnitt

**Prompt (gekürzt):**
> Unsere Aufgabe ist: Die Studierenden entwickeln eine Web-Anwendung für einen einfachen Geschäftsprozess aus Wirtschaft oder Verwaltung. […] Wir wollen mit dir step by step eine Website aufbauen. Es geht im Groben darum, einen Fertigungsauftrag so digital zu gestalten, dass sich die Zeiterfassung sowie Arbeitsschritte für den Auftrag im System buchen lassen, um eine Übersicht sowie Nachkalkulation zu ermöglichen.

Mitgegeben: Veranstaltungsunterlage 1, HTML-Grundlagen, Fachkonzept, Technisches Konzept und Präsentation des Beispielprojekts „Fertigungssteuerung“.

**Ergebnis:**
- Auswertung der Bewertungskriterien: mindestens 10 Anwendungsfälle, KI-Konzept, Anteile je Teammitglied
- Abgrenzung zum Beispielprojekt: dort Material und Kapazität, bei uns Zeiten, Arbeitsschritte und Kosten
- Vorschlag für Rollen, Auftragsstatus, 14 Anwendungsfälle und einen Fahrplan bis zur Abschlusspräsentation
- Rechenbeispiel für die Nachkalkulation (Soll-Zeit = Rüstzeit + Menge × Stückzeit, Kosten über Stundensätze)
- Technik bleibt bei HTML, PHP und MariaDB ohne Framework, damit wir jede Zeile erklären können

**Unsere Entscheidung:** offen, Vorschlag wird im Team besprochen

---

## 2 · 27.09.2026 · Klickbarer HTML-Prototyp

**Prompt (gekürzt):**
> Das kannst du runterladen: XAMPP und VS Code. Nochmal zur Info, das müssen wir morgen abgeben. (dazu ein Screenshot der Moodle-Einträge „HTML-Basis“ und „Präsentation 1. Veranstaltung“)

**Ergebnis:**
- VS Code per winget installiert. XAMPP abgebrochen, weil die Windows-Sicherheitsabfrage nicht bestätigt wurde
- 11 statische HTML-Seiten und eine CSS-Datei in `prototyp/`: Anmeldung, Dashboard, Aufträge, Auftrag anlegen, Auftragsdetail, Werker-Terminal, Nachkalkulation, Auswertung, Artikel und Arbeitspläne, Arbeitsplätze, Benutzer
- Beispieldaten über alle Seiten stimmig und nachgerechnet
- Von der KI selbst gefundene und behobene Fehler:
  - Tabellen haben auf schmalen Bildschirmen die Seite seitlich verbreitert. Ursache: Grid-Spalten schrumpfen nicht unter die Tabellenbreite. Lösung: `min-width: 0` für die Spalten
  - Die Tabelle „Gerade in Arbeit“ war im Dashboard zu eng. Lösung: volle Breite, darunter Ereignisse und Liefertermine
- Kurzkonzept als PDF in `doku/`

**Geprüft:** alle Links, kein seitliches Überlaufen bei 375, 750, 1024 und 1366 px Breite

---

<!-- Vorlage für weitere Einträge:

## N · TT.MM.JJJJ · Thema

**Wer:** Name
**Prompt (gekürzt):**
> …

**Ergebnis:**
- …

**Was wir angepasst oder verworfen haben:**
- …
-->
