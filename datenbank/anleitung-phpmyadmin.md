# Datenbank in phpMyAdmin öffnen

Schritt für Schritt: die SQL-Skripte einspielen und die Datensätze ansehen. Voraussetzung ist XAMPP, das MariaDB und phpMyAdmin schon enthält.

Eine `.sql`-Datei doppelt anklicken öffnet nur den Text. Die Daten entstehen erst, wenn MariaDB die Datei ausführt.

## 1. XAMPP starten

XAMPP Control Panel öffnen und bei **Apache** und **MySQL** auf „Start“ klicken. Beide Zeilen werden grün. Apache ist nötig, weil phpMyAdmin eine Webseite ist.

## 2. phpMyAdmin öffnen

Im Browser http://localhost/phpmyadmin aufrufen oder im Control Panel bei MySQL auf „Admin“ klicken. XAMPP meldet sich automatisch als `root` ohne Passwort an.

## 3. Skripte importieren

Immer in dieser Reihenfolge, jede Datei einzeln:

| Schritt | Datei | Ergebnis |
|---|---|---|
| 1 | `01_schema.sql` | Datenbank `fa_digital` mit 14 leeren Tabellen |
| 2 | `02_testdaten.sql` | Beispieldaten passend zum Prototyp |
| 3 | `03_sichten.sql` | 14 Sichten (`v_…`) für Hallenansicht, Nachkalkulation usw. |

Für jede Datei:

1. Oben auf den Reiter **Importieren** klicken
2. „Datei auswählen“ und die Datei aus dem Ordner `datenbank/` wählen
3. Ganz unten auf **Import** klicken
4. Grüne Meldung „Der Import wurde erfolgreich abgeschlossen“ abwarten

Was links ausgewählt ist, spielt keine Rolle. Jede Datei wählt `fa_digital` selbst aus.

**Achtung:** `01_schema.sql` löscht eine vorhandene `fa_digital` mit allen Daten. Das ist gewollt, damit man jederzeit neu anfangen kann.

## 4. Datensätze ansehen

1. Links auf `fa_digital` klicken (eventuell F5 drücken, falls sie noch nicht da ist)
2. Eine Tabelle anklicken, z. B. `fertigungsauftrag`
3. Der Reiter **Anzeigen** listet die Datensätze auf

Die Sichten stehen links unter „Ansichten“ und funktionieren genauso. Für den Anfang sind diese interessant:

| Sicht | Zeigt |
|---|---|
| `v_hallenansicht` | Zustand jedes Arbeitsplatzes mit Ampel |
| `v_auftragsuebersicht` | alle Aufträge mit Status und Verzug |
| `v_nachkalkulation_auftrag` | Soll-Ist-Vergleich und Kosten je Auftrag |
| `v_unterbrechung_auswertung` | Unterbrechungen nach Grund |

## 5. Eigene Abfragen ausführen

1. `fa_digital` links anklicken, dann Reiter **SQL**
2. Abfrage eintippen oder aus `04_abfragen.sql` kopieren, z. B.

   ```sql
   SELECT * FROM v_hallenansicht;
   ```

3. Auf **OK** klicken

Am übersichtlichsten ist es, die Abfragen aus `04_abfragen.sql` einzeln zu kopieren. `05_beispielablaeufe.sql` lässt sich auch komplett einspielen. Alle Abläufe enden mit ROLLBACK, die Testdaten ändern sich dabei nicht.

## Häufige Probleme

| Problem | Lösung |
|---|---|
| MySQL startet nicht, Port 3306 belegt | Läuft schon ein anderes MySQL? Dieses beenden oder in XAMPP einen anderen Port einstellen |
| phpMyAdmin lädt nicht | Apache ist nicht gestartet |
| `#1049 Unknown database 'fa_digital'` | `01_schema.sql` wurde noch nicht importiert |
| Umlaute kaputt | beim Import unter „Zeichensatz der Datei“ `utf-8` wählen (Standard) |
| Laufende Buchungen sehr lang, Termine alle im Verzug | Die Testdaten rechnen ab dem Importzeitpunkt. Nach ein paar Tagen 01, 02 und 03 neu einspielen |

Den Import über die Kommandozeile beschreibt `README.md`.
