-- =====================================================================
-- FA-Digital: Beispielabläufe (schreibend)
-- Zeigt, welche SQL-Befehle die Anwendung für die wichtigsten Vorgänge ausführt.
-- Jeder Ablauf läuft in einer Transaktion und wird am Ende mit ROLLBACK
-- zurückgenommen. Die Testdaten bleiben also unverändert.
-- In PHP läuft derselbe Ablauf mit COMMIT statt ROLLBACK.
--
-- Ausführen nach 01 bis 03:  mysql -u root fa_digital < 05_beispielablaeufe.sql
-- =====================================================================

USE fa_digital;
SET NAMES utf8mb4;
SET @anna   = (SELECT id FROM benutzer WHERE personalnummer = '1001');
SET @lena   = (SELECT id FROM benutzer WHERE personalnummer = '1002');
SET @jonas  = (SELECT id FROM benutzer WHERE personalnummer = '1042');
SET @paul   = (SELECT id FROM benutzer WHERE personalnummer = '1045');

-- ---------------------------------------------------------------------
-- Ablauf 1: Fertigungsauftrag anlegen, mit gefordertem Werkszertifikat 2.2
-- Arbeitsgänge und Checklisten entstehen als Kopie aus Arbeitsplan und Prüfmerkmalen.
-- ---------------------------------------------------------------------
START TRANSACTION;

INSERT INTO fertigungsauftrag (auftragsnummer, artikel_id, menge, kunde, liefertermin, angelegt_von)
VALUES ('FA-1007', (SELECT id FROM artikel WHERE artikelnummer = 'W-200'), 60,
        'Nordwerk Maschinenbau GmbH', CURDATE() + INTERVAL 21 DAY, @anna);
SET @fa = LAST_INSERT_ID();

INSERT INTO auftrag_zertifikat (fertigungsauftrag_id, zertifikatsart_id)
VALUES (@fa, (SELECT id FROM zertifikatsart WHERE kuerzel = '2.2'));

INSERT INTO arbeitsgang (fertigungsauftrag_id, arbeitsplan_position_id, ag_nummer, bezeichnung, arbeitsplatz_id,
                         soll_ruestzeit_min, soll_stueckzeit_min, stundensatz)
SELECT @fa, pos.id, pos.ag_nummer, pos.bezeichnung, pos.arbeitsplatz_id, pos.ruestzeit_min, pos.stueckzeit_min, ap.stundensatz
FROM arbeitsplan_position pos
JOIN arbeitsplatz ap ON ap.id = pos.arbeitsplatz_id
WHERE pos.artikel_id = (SELECT artikel_id FROM fertigungsauftrag WHERE id = @fa);

INSERT INTO pruefpunkt (arbeitsgang_id, pruefmerkmal_id, reihenfolge, bezeichnung, art, sollwert, untere_grenze, obere_grenze, einheit)
SELECT ag.id, pm.id, pm.reihenfolge, pm.bezeichnung, pm.art, pm.sollwert, pm.untere_grenze, pm.obere_grenze, pm.einheit
FROM arbeitsgang ag
JOIN pruefmerkmal pm ON pm.arbeitsplan_position_id = ag.arbeitsplan_position_id
WHERE ag.fertigungsauftrag_id = @fa
  AND (pm.zertifikatsart_id IS NULL
       OR pm.zertifikatsart_id IN (SELECT zertifikatsart_id FROM auftrag_zertifikat WHERE fertigungsauftrag_id = @fa));

-- Vorschau für die Arbeitsvorbereitung: Arbeitsgänge mit Soll-Zeit und Soll-Kosten
SELECT ag_nummer, arbeitsgang, arbeitsplatz, soll_zeit_min, soll_kosten
FROM v_nachkalkulation_arbeitsgang WHERE fertigungsauftrag_id = @fa ORDER BY ag_nummer;

-- Checkliste für den Werker: 3 Standardprüfungen plus 2 Prüfungen wegen Werkszeugnis 2.2
SELECT ag_nummer, pruefpunkt, art FROM v_checkliste WHERE auftragsnummer = 'FA-1007' ORDER BY ag_nummer, reihenfolge;

ROLLBACK;

-- ---------------------------------------------------------------------
-- Ablauf 2: Werker Jonas meldet FA-1002 · AG 20 fertig und startet danach FA-1003 · AG 20
-- ---------------------------------------------------------------------
START TRANSACTION;

SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id
           WHERE fa.auftragsnummer = 'FA-1002' AND ag.ag_nummer = 20);

-- laufende Buchung beenden
UPDATE zeitbuchung SET ende = NOW() WHERE arbeitsgang_id = @ag AND ende IS NULL AND gueltig = 1;

-- fertig melden, aber nur wenn kein Prüfpunkt mehr offen ist
UPDATE arbeitsgang
SET status = 'erledigt', gutmenge = 30, ausschuss = 0, fertig_gemeldet_von = @jonas, fertig_gemeldet_am = NOW()
WHERE id = @ag
  AND NOT EXISTS (SELECT 1 FROM pruefpunkt WHERE arbeitsgang_id = @ag AND ergebnis = 'offen');
SELECT ROW_COUNT() AS fertig_gemeldet;   -- 1 = erfolgreich, 0 = es waren noch Prüfpunkte offen

-- neuen Arbeitsgang mit Rüsten starten
SET @neu = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id
            WHERE fa.auftragsnummer = 'FA-1003' AND ag.ag_nummer = 20);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, erfasst_von)
VALUES (@neu, @jonas, 'ruesten', NOW(), @jonas);
UPDATE arbeitsgang SET status = 'laeuft' WHERE id = @neu;

-- Die Hallenansicht zeigt jetzt FA-1003 an der CNC-Drehmaschine
SELECT arbeitsplatz, zustand, auftragsnummer, ag_nummer, werker, laufende_taetigkeit, ampel
FROM v_hallenansicht WHERE arbeitsplatz_nummer = 'AP-20';

ROLLBACK;

-- ---------------------------------------------------------------------
-- Ablauf 3: Paul hat FA-1004 · AG 10 vor 20 min unterbrochen (Material fehlt)
-- und vor 5 min fortgesetzt
-- ---------------------------------------------------------------------
START TRANSACTION;

SET @ag  = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id
            WHERE fa.auftragsnummer = 'FA-1004' AND ag.ag_nummer = 10);
SET @t1  = NOW() - INTERVAL 20 MINUTE;
SET @t2  = NOW() - INTERVAL 5 MINUTE;

UPDATE zeitbuchung SET ende = @t1 WHERE arbeitsgang_id = @ag AND ende IS NULL AND gueltig = 1;
INSERT INTO unterbrechung (arbeitsgang_id, benutzer_id, unterbrechungsgrund_id, beginn)
VALUES (@ag, @paul, (SELECT id FROM unterbrechungsgrund WHERE bezeichnung = 'Material fehlt'), @t1);
UPDATE arbeitsgang SET status = 'unterbrochen' WHERE id = @ag;

SELECT arbeitsplatz, zustand, unterbrechungsgrund FROM v_hallenansicht WHERE arbeitsplatz_nummer = 'AP-10';

-- fortsetzen
UPDATE unterbrechung SET ende = @t2 WHERE arbeitsgang_id = @ag AND ende IS NULL;
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, erfasst_von, erfasst_am)
VALUES (@ag, @paul, 'bearbeiten', @t2, @paul, @t2);
UPDATE arbeitsgang SET status = 'laeuft' WHERE id = @ag;

-- Die 15 min Unterbrechung zählen nicht zur Ist-Zeit: Sie ist 15 min kleiner als ohne Unterbrechung
SELECT auftragsnummer, ag_nummer, ist_zeit_min FROM v_arbeitsgang_zeit WHERE arbeitsgang_id = @ag;

ROLLBACK;

-- ---------------------------------------------------------------------
-- Ablauf 4: Checkliste. Messwert außerhalb der Toleranz, Nacharbeit, erneute Messung
-- ---------------------------------------------------------------------
START TRANSACTION;

SET @pp = (SELECT pp.id FROM pruefpunkt pp
           JOIN arbeitsgang ag ON ag.id = pp.arbeitsgang_id
           JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id
           WHERE fa.auftragsnummer = 'FA-1003' AND ag.ag_nummer = 20 AND pp.reihenfolge = 1);

-- Das Ergebnis ergibt sich direkt aus den Toleranzgrenzen
UPDATE pruefpunkt
SET istwert = 30.150,
    ergebnis = IF(30.150 BETWEEN untere_grenze AND obere_grenze, 'in_ordnung', 'nicht_in_ordnung'),
    bemerkung = 'zu lang, wird nachgearbeitet', geprueft_von = @jonas, geprueft_am = NOW()
WHERE id = @pp;
SELECT pruefpunkt, istwert, in_toleranz, ergebnis FROM v_checkliste WHERE pruefpunkt_id = @pp;

UPDATE pruefpunkt
SET istwert = 30.040,
    ergebnis = IF(30.040 BETWEEN untere_grenze AND obere_grenze, 'in_ordnung', 'nicht_in_ordnung'),
    bemerkung = 'nach Nacharbeit gemessen', geprueft_am = NOW()
WHERE id = @pp;
SELECT pruefpunkt, istwert, in_toleranz, ergebnis FROM v_checkliste WHERE pruefpunkt_id = @pp;

ROLLBACK;

-- ---------------------------------------------------------------------
-- Ablauf 5: Meisterin korrigiert eine Buchung. Die alte Buchung bleibt erhalten.
-- Ohne Begründung lehnt die Datenbank die Korrektur ab (CHECK ck_zeitbuchung_begruendung):
--   INSERT INTO zeitbuchung (..., quelle) VALUES (..., 'korrektur');  →  Fehler 4025
-- ---------------------------------------------------------------------
START TRANSACTION;

SET @alt = (SELECT zb.id FROM zeitbuchung zb
            JOIN arbeitsgang ag ON ag.id = zb.arbeitsgang_id
            JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id
            WHERE fa.auftragsnummer = 'FA-1002' AND ag.ag_nummer = 20 AND zb.taetigkeit = 'ruesten' AND zb.gueltig = 1);

INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, quelle, ersetzt_buchung_id, begruendung, erfasst_von)
SELECT arbeitsgang_id, benutzer_id, taetigkeit, beginn, beginn + INTERVAL 25 MINUTE, 'korrektur', id,
       'Rüsten endete laut Werker nach 25 min', @lena
FROM zeitbuchung WHERE id = @alt;
UPDATE zeitbuchung SET gueltig = 0 WHERE id = @alt;

SELECT taetigkeit, TIMESTAMPDIFF(MINUTE, beginn, ende) AS dauer_min, quelle, gueltig, begruendung
FROM zeitbuchung WHERE id = @alt OR ersetzt_buchung_id = @alt;

ROLLBACK;

-- ---------------------------------------------------------------------
-- Ablauf 6: Werkszertifikat für FA-1001 freigeben, nur wenn alles in Ordnung ist
-- ---------------------------------------------------------------------
START TRANSACTION;

UPDATE auftrag_zertifikat az
JOIN v_zertifikat_status s ON s.auftrag_zertifikat_id = az.id
SET az.status = 'freigegeben', az.erstellt_am = NOW(), az.freigegeben_von = @lena, az.freigegeben_am = NOW(),
    az.dateiname = CONCAT('WZ_', s.auftragsnummer, '_', s.kuerzel, '.pdf')
WHERE s.auftragsnummer = 'FA-1001' AND s.bereit_zur_freigabe = 1;

SELECT auftragsnummer, kuerzel, status, freigegeben_am, dateiname FROM v_zertifikat_status WHERE auftragsnummer = 'FA-1001';

ROLLBACK;

-- ---------------------------------------------------------------------
-- Ablauf 7: Übernahme einer Abweichung in die neue Kalkulation
-- Die Rüstzeit für das Drehen der W-200 steigt von 30 auf 39 min.
-- Laufende Aufträge behalten ihre Soll-Zeit, neue Aufträge rechnen mit 39 min.
-- ---------------------------------------------------------------------
START TRANSACTION;

SET @pos = (SELECT arbeitsplan_position_id FROM v_sollzeit_vorschlag WHERE artikelnummer = 'W-200' AND ag_nummer = 20);

INSERT INTO arbeitsplan_aenderung (arbeitsplan_position_id, feld, alter_wert, neuer_wert, begruendung, geaendert_von)
SELECT arbeitsplan_position_id, 'ruestzeit', soll_ruestzeit_min, vorschlag_ruestzeit_min,
       CONCAT('Ø Ist-Rüstzeit aus ', anzahl_auftraege, ' abgeschlossenen Aufträgen'), @anna
FROM v_sollzeit_vorschlag WHERE arbeitsplan_position_id = @pos;

UPDATE arbeitsplan_position SET ruestzeit_min = (SELECT neuer_wert FROM arbeitsplan_aenderung WHERE id = LAST_INSERT_ID())
WHERE id = @pos;

SELECT pos.ruestzeit_min AS neu_im_arbeitsplan,
       (SELECT ag.soll_ruestzeit_min FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id
        WHERE fa.auftragsnummer = 'FA-1004' AND ag.ag_nummer = 20) AS laufender_auftrag_fa_1004
FROM arbeitsplan_position pos WHERE pos.id = @pos;

ROLLBACK;

-- ---------------------------------------------------------------------
-- Ablauf 8: Nachkalkulation von FA-1001 abschließen
-- ---------------------------------------------------------------------
START TRANSACTION;

UPDATE fertigungsauftrag SET status = 'nachkalkuliert', nachkalkuliert_am = NOW()
WHERE auftragsnummer = 'FA-1001' AND status = 'fertig';
SELECT auftragsnummer, status, nachkalkuliert_am FROM fertigungsauftrag WHERE auftragsnummer = 'FA-1001';

ROLLBACK;
