-- =====================================================================
-- FA-Digital: Sichten (Views)
-- Berechnete Werte (Soll-/Ist-Zeiten, Kosten, Ampeln, Vorschläge) werden
-- nicht gespeichert, sondern hier aus den Tabellen abgeleitet.
-- So gibt es keine doppelten Daten, die sich widersprechen können.
--
-- Ausführen nach 01_schema.sql:  mysql -u root fa_digital < 03_sichten.sql
-- =====================================================================

USE fa_digital;

-- ---------------------------------------------------------------------
-- Grundlage: Soll- und Ist-Zeiten je Arbeitsgang
-- Soll-Zeit = Rüstzeit + Menge × Stückzeit
-- Ist-Zeit  = Summe der gültigen Zeitbuchungen (laufende Buchungen bis jetzt)
-- Unterbrechungen zählen nicht zur Ist-Zeit.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_arbeitsgang_zeit AS
SELECT
  ag.id                                                       AS arbeitsgang_id,
  fa.id                                                       AS fertigungsauftrag_id,
  fa.auftragsnummer,
  fa.menge,
  ag.ag_nummer,
  ag.bezeichnung                                              AS arbeitsgang,
  ag.arbeitsplatz_id,
  ap.bezeichnung                                              AS arbeitsplatz,
  ag.status,
  ag.stundensatz,
  ag.soll_ruestzeit_min                                       AS soll_ruesten_min,
  fa.menge * ag.soll_stueckzeit_min                           AS soll_bearbeiten_min,
  ag.soll_ruestzeit_min + fa.menge * ag.soll_stueckzeit_min   AS soll_zeit_min,
  COALESCE(SUM(CASE WHEN zb.taetigkeit = 'ruesten'
                    THEN TIMESTAMPDIFF(MINUTE, zb.beginn, COALESCE(zb.ende, NOW())) END), 0) AS ist_ruesten_min,
  COALESCE(SUM(CASE WHEN zb.taetigkeit = 'bearbeiten'
                    THEN TIMESTAMPDIFF(MINUTE, zb.beginn, COALESCE(zb.ende, NOW())) END), 0) AS ist_bearbeiten_min,
  COALESCE(SUM(TIMESTAMPDIFF(MINUTE, zb.beginn, COALESCE(zb.ende, NOW()))), 0)           AS ist_zeit_min
FROM arbeitsgang ag
JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id
JOIN arbeitsplatz ap      ON ap.id = ag.arbeitsplatz_id
LEFT JOIN zeitbuchung zb  ON zb.arbeitsgang_id = ag.id AND zb.gueltig = 1
GROUP BY ag.id, fa.id, fa.auftragsnummer, fa.menge, ag.ag_nummer, ag.bezeichnung, ag.arbeitsplatz_id,
         ap.bezeichnung, ag.status, ag.stundensatz, ag.soll_ruestzeit_min, ag.soll_stueckzeit_min;

-- ---------------------------------------------------------------------
-- Anwendungsfall 1: Arbeitsvorrat am Arbeitsplatz
-- Nur Arbeitsgänge freigegebener Aufträge. „wartet“, solange ein Vorgänger offen ist.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_arbeitsvorrat AS
SELECT
  ag.id               AS arbeitsgang_id,
  ap.id               AS arbeitsplatz_id,
  ap.nummer           AS arbeitsplatz_nummer,
  ap.bezeichnung      AS arbeitsplatz,
  fa.auftragsnummer,
  ag.ag_nummer,
  ag.bezeichnung      AS arbeitsgang,
  ar.artikelnummer,
  ar.bezeichnung      AS artikel,
  fa.menge,
  fa.liefertermin,
  z.soll_zeit_min,
  z.ist_zeit_min,
  CASE
    WHEN ag.status IN ('laeuft', 'unterbrochen') THEN ag.status
    WHEN EXISTS (SELECT 1 FROM arbeitsgang v
                 WHERE v.fertigungsauftrag_id = ag.fertigungsauftrag_id
                   AND v.ag_nummer < ag.ag_nummer AND v.status <> 'erledigt') THEN 'wartet'
    ELSE 'bereit'
  END                 AS bereitschaft,
  (SELECT MAX(v.ag_nummer) FROM arbeitsgang v
   WHERE v.fertigungsauftrag_id = ag.fertigungsauftrag_id
     AND v.ag_nummer < ag.ag_nummer AND v.status <> 'erledigt') AS wartet_auf_ag
FROM arbeitsgang ag
JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id AND fa.status IN ('freigegeben', 'in_arbeit')
JOIN artikel ar           ON ar.id = fa.artikel_id
JOIN arbeitsplatz ap      ON ap.id = ag.arbeitsplatz_id
JOIN v_arbeitsgang_zeit z ON z.arbeitsgang_id = ag.id
WHERE ag.status <> 'erledigt';

-- ---------------------------------------------------------------------
-- Anwendungsfall 4: Checkliste mit Toleranzprüfung
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_checkliste AS
SELECT
  pp.id               AS pruefpunkt_id,
  fa.auftragsnummer,
  ag.id               AS arbeitsgang_id,
  ag.ag_nummer,
  ag.bezeichnung      AS arbeitsgang,
  pp.reihenfolge,
  pp.bezeichnung      AS pruefpunkt,
  pp.art,
  pp.sollwert,
  pp.untere_grenze,
  pp.obere_grenze,
  pp.einheit,
  pp.istwert,
  CASE WHEN pp.art = 'messwert' AND pp.istwert IS NOT NULL
       THEN pp.istwert BETWEEN pp.untere_grenze AND pp.obere_grenze END AS in_toleranz,
  pp.ergebnis,
  pp.bemerkung,
  CONCAT(b.vorname, ' ', b.nachname) AS geprueft_von,
  pp.geprueft_am
FROM pruefpunkt pp
JOIN arbeitsgang ag       ON ag.id = pp.arbeitsgang_id
JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id
LEFT JOIN benutzer b      ON b.id = pp.geprueft_von;

-- ---------------------------------------------------------------------
-- Anwendungsfall 7: Hallenansicht, eine Zeile je aktivem Arbeitsplatz
-- Ampel: bis 80 % der Soll-Zeit grün, bis 100 % gelb, darüber rot
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_hallenansicht AS
SELECT
  ap.id                AS arbeitsplatz_id,
  ap.nummer            AS arbeitsplatz_nummer,
  ap.bezeichnung       AS arbeitsplatz,
  CASE WHEN ag.id IS NULL THEN 'frei'
       WHEN ag.status = 'unterbrochen' THEN 'unterbrochen'
       ELSE 'belegt' END                                         AS zustand,
  fa.auftragsnummer,
  ar.bezeichnung       AS artikel,
  fa.menge,
  ag.ag_nummer,
  ag.bezeichnung       AS arbeitsgang,
  CONCAT(b.vorname, ' ', b.nachname)                            AS werker,
  CASE WHEN zb.ende IS NULL THEN zb.taetigkeit END              AS laufende_taetigkeit,
  CASE WHEN zb.ende IS NULL THEN zb.beginn END                  AS laeuft_seit,
  z.soll_zeit_min,
  z.ist_zeit_min,
  ROUND(100 * z.ist_zeit_min / NULLIF(z.soll_zeit_min, 0))      AS verbraucht_prozent,
  CASE WHEN ag.id IS NULL THEN NULL
       WHEN z.ist_zeit_min <= 0.8 * z.soll_zeit_min THEN 'gruen'
       WHEN z.ist_zeit_min <= z.soll_zeit_min THEN 'gelb'
       ELSE 'rot' END                                            AS ampel,
  ug.bezeichnung       AS unterbrechungsgrund,
  u.beginn             AS unterbrochen_seit,
  TIMESTAMPDIFF(MINUTE, u.beginn, NOW())                        AS unterbrochen_min
FROM arbeitsplatz ap
LEFT JOIN arbeitsgang ag        ON ag.arbeitsplatz_id = ap.id AND ag.status IN ('laeuft', 'unterbrochen')
LEFT JOIN fertigungsauftrag fa  ON fa.id = ag.fertigungsauftrag_id
LEFT JOIN artikel ar            ON ar.id = fa.artikel_id
LEFT JOIN v_arbeitsgang_zeit z  ON z.arbeitsgang_id = ag.id
LEFT JOIN zeitbuchung zb        ON zb.id = (SELECT MAX(x.id) FROM zeitbuchung x
                                            WHERE x.arbeitsgang_id = ag.id AND x.gueltig = 1)
LEFT JOIN benutzer b            ON b.id = zb.benutzer_id
LEFT JOIN unterbrechung u       ON u.arbeitsgang_id = ag.id AND u.ende IS NULL
LEFT JOIN unterbrechungsgrund ug ON ug.id = u.unterbrechungsgrund_id
WHERE ap.aktiv = 1;

-- ---------------------------------------------------------------------
-- Anwendungsfall 7: letzte Buchungen aus der Werkstatt für die Hallenansicht
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_letzte_buchungen AS
SELECT
  CASE WHEN zb.quelle = 'terminal' THEN zb.beginn ELSE zb.erfasst_am END AS zeitpunkt,
  CONCAT(e.vorname, ' ', e.nachname)  AS mitarbeiter,
  ap.bezeichnung                      AS arbeitsplatz,
  fa.auftragsnummer,
  ag.ag_nummer,
  CASE WHEN zb.quelle = 'terminal'
       THEN CONCAT('Start ', IF(zb.taetigkeit = 'ruesten', 'Rüsten', 'Bearbeiten'))
       ELSE CONCAT('Nachtrag: ', zb.begruendung) END                   AS buchung
FROM zeitbuchung zb
JOIN arbeitsgang ag       ON ag.id = zb.arbeitsgang_id
JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id
JOIN arbeitsplatz ap      ON ap.id = ag.arbeitsplatz_id
JOIN benutzer e           ON e.id = zb.erfasst_von
WHERE zb.gueltig = 1
UNION ALL
SELECT
  u.beginn,
  CONCAT(b.vorname, ' ', b.nachname),
  ap.bezeichnung,
  fa.auftragsnummer,
  ag.ag_nummer,
  CONCAT('Unterbrochen: ', ug.bezeichnung)
FROM unterbrechung u
JOIN arbeitsgang ag         ON ag.id = u.arbeitsgang_id
JOIN fertigungsauftrag fa   ON fa.id = ag.fertigungsauftrag_id
JOIN arbeitsplatz ap        ON ap.id = ag.arbeitsplatz_id
JOIN benutzer b             ON b.id = u.benutzer_id
JOIN unterbrechungsgrund ug ON ug.id = u.unterbrechungsgrund_id;

-- ---------------------------------------------------------------------
-- Anwendungsfall 8: Auftragsübersicht für alle Rollen
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_auftragsuebersicht AS
SELECT
  fa.id                AS fertigungsauftrag_id,
  fa.auftragsnummer,
  ar.artikelnummer,
  ar.bezeichnung       AS artikel,
  fa.menge,
  fa.kunde,
  fa.liefertermin,
  fa.status,
  (fa.liefertermin < CURDATE() AND fa.status IN ('angelegt', 'freigegeben', 'in_arbeit')) AS verzug,
  SUM(z.status = 'erledigt')   AS ag_erledigt,
  COUNT(*)                     AS ag_gesamt,
  SUM(z.soll_zeit_min)         AS soll_zeit_min,
  SUM(z.ist_zeit_min)          AS ist_zeit_min,
  (SELECT CONCAT(a2.ag_nummer, ' ', a2.bezeichnung) FROM arbeitsgang a2
   WHERE a2.fertigungsauftrag_id = fa.id AND a2.status <> 'erledigt'
   ORDER BY a2.ag_nummer LIMIT 1)                                        AS aktueller_arbeitsgang,
  (SELECT COUNT(*) FROM pruefpunkt pp JOIN arbeitsgang a3 ON a3.id = pp.arbeitsgang_id
   WHERE a3.fertigungsauftrag_id = fa.id AND pp.ergebnis = 'offen')      AS pruefpunkte_offen,
  (SELECT COUNT(*) FROM pruefpunkt pp JOIN arbeitsgang a3 ON a3.id = pp.arbeitsgang_id
   WHERE a3.fertigungsauftrag_id = fa.id AND pp.ergebnis = 'nicht_in_ordnung') AS pruefpunkte_nicht_in_ordnung,
  (SELECT GROUP_CONCAT(CONCAT(za.kuerzel, ' ', azz.status) ORDER BY za.kuerzel SEPARATOR ', ')
   FROM auftrag_zertifikat azz JOIN zertifikatsart za ON za.id = azz.zertifikatsart_id
   WHERE azz.fertigungsauftrag_id = fa.id)                               AS zertifikate
FROM fertigungsauftrag fa
JOIN artikel ar           ON ar.id = fa.artikel_id
JOIN v_arbeitsgang_zeit z ON z.fertigungsauftrag_id = fa.id
WHERE fa.status <> 'nachkalkuliert'
GROUP BY fa.id, fa.auftragsnummer, ar.artikelnummer, ar.bezeichnung, fa.menge, fa.kunde, fa.liefertermin, fa.status;

-- ---------------------------------------------------------------------
-- Schlanke Meister-Ansicht (Rückmeldung vom 01.10.2026):
-- nur die Aufträge, die gerade in der Werkstatt sind, mit dem Nötigsten
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_meister_auftraege AS
SELECT
  auftragsnummer,
  artikel,
  menge,
  liefertermin,
  verzug,
  status,
  CONCAT(ag_erledigt, ' von ', ag_gesamt) AS fortschritt,
  aktueller_arbeitsgang,
  ROUND(100 * ist_zeit_min / NULLIF(soll_zeit_min, 0)) AS zeit_verbraucht_prozent,
  pruefpunkte_offen,
  pruefpunkte_nicht_in_ordnung,
  zertifikate
FROM v_auftragsuebersicht
WHERE status IN ('freigegeben', 'in_arbeit', 'fertig');

-- ---------------------------------------------------------------------
-- Anwendungsfall 9: Stand der Werkszertifikate
-- Freigabe ist erst möglich, wenn der Auftrag fertig ist und alle Prüfpunkte in Ordnung sind
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_zertifikat_status AS
SELECT
  azz.id               AS auftrag_zertifikat_id,
  fa.auftragsnummer,
  ar.artikelnummer,
  ar.bezeichnung       AS artikel,
  fa.menge,
  fa.kunde,
  fa.status            AS auftragsstatus,
  za.kuerzel,
  za.bezeichnung       AS zertifikat,
  azz.status,
  COUNT(pp.id)                              AS pruefpunkte,
  COALESCE(SUM(pp.ergebnis = 'offen'), 0)   AS offen,
  COALESCE(SUM(pp.ergebnis = 'nicht_in_ordnung'), 0) AS nicht_in_ordnung,
  (fa.status IN ('fertig', 'nachkalkuliert')
   AND COALESCE(SUM(pp.ergebnis <> 'in_ordnung'), 0) = 0) AS bereit_zur_freigabe,
  azz.freigegeben_am,
  azz.dateiname
FROM auftrag_zertifikat azz
JOIN fertigungsauftrag fa ON fa.id = azz.fertigungsauftrag_id
JOIN artikel ar           ON ar.id = fa.artikel_id
JOIN zertifikatsart za    ON za.id = azz.zertifikatsart_id
LEFT JOIN arbeitsgang ag  ON ag.fertigungsauftrag_id = fa.id
LEFT JOIN pruefpunkt pp   ON pp.arbeitsgang_id = ag.id
GROUP BY azz.id, fa.auftragsnummer, ar.artikelnummer, ar.bezeichnung, fa.menge, fa.kunde, fa.status,
         za.kuerzel, za.bezeichnung, azz.status, azz.freigegeben_am, azz.dateiname;

-- ---------------------------------------------------------------------
-- Anwendungsfall 10: Nachkalkulation je Arbeitsgang
-- Kosten = Zeit in Stunden × Stundensatz zum Zeitpunkt der Auftragsanlage
-- Ampel: Abweichung bis +5 % grün, bis +10 % gelb, darüber rot
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_nachkalkulation_arbeitsgang AS
SELECT
  z.*,
  ROUND(z.soll_zeit_min / 60 * z.stundensatz, 2)                                  AS soll_kosten,
  ROUND(z.ist_zeit_min  / 60 * z.stundensatz, 2)                                  AS ist_kosten,
  ROUND(100 * (z.ist_zeit_min - z.soll_zeit_min) / NULLIF(z.soll_zeit_min, 0), 1) AS abweichung_prozent,
  CASE WHEN z.ist_zeit_min <= 1.05 * z.soll_zeit_min THEN 'gruen'
       WHEN z.ist_zeit_min <= 1.10 * z.soll_zeit_min THEN 'gelb'
       ELSE 'rot' END                                                              AS ampel
FROM v_arbeitsgang_zeit z;

-- ---------------------------------------------------------------------
-- Anwendungsfälle 10 und 11: Nachkalkulation je Auftrag mit Kosten pro Gutstück
-- Gutmenge = Gutmenge des letzten Arbeitsgangs, Ausschuss = Summe über alle Arbeitsgänge
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_nachkalkulation_auftrag AS
SELECT
  k.*,
  ROUND(100 * (k.ist_kosten - k.soll_kosten) / NULLIF(k.soll_kosten, 0), 1) AS abweichung_prozent,
  CASE WHEN k.ist_kosten <= 1.05 * k.soll_kosten THEN 'gruen'
       WHEN k.ist_kosten <= 1.10 * k.soll_kosten THEN 'gelb'
       ELSE 'rot' END                                                       AS ampel,
  ROUND(k.soll_kosten / k.menge, 2)                                         AS soll_kosten_je_stueck,
  ROUND(k.ist_kosten / NULLIF(k.gutmenge, 0), 2)                            AS ist_kosten_je_gutstueck
FROM (
  SELECT
    fa.id              AS fertigungsauftrag_id,
    fa.auftragsnummer,
    ar.artikelnummer,
    ar.bezeichnung     AS artikel,
    fa.menge,
    fa.status,
    fa.abgeschlossen_am,
    SUM(n.soll_zeit_min)                               AS soll_zeit_min,
    SUM(n.ist_zeit_min)                                AS ist_zeit_min,
    ROUND(SUM(n.soll_zeit_min / 60 * n.stundensatz), 2) AS soll_kosten,
    ROUND(SUM(n.ist_zeit_min  / 60 * n.stundensatz), 2) AS ist_kosten,
    (SELECT a2.gutmenge FROM arbeitsgang a2 WHERE a2.fertigungsauftrag_id = fa.id
     ORDER BY a2.ag_nummer DESC LIMIT 1)                AS gutmenge,
    (SELECT SUM(a3.ausschuss) FROM arbeitsgang a3 WHERE a3.fertigungsauftrag_id = fa.id) AS ausschuss
  FROM fertigungsauftrag fa
  JOIN artikel ar           ON ar.id = fa.artikel_id
  JOIN v_arbeitsgang_zeit n ON n.fertigungsauftrag_id = fa.id
  GROUP BY fa.id, fa.auftragsnummer, ar.artikelnummer, ar.bezeichnung, fa.menge, fa.status, fa.abgeschlossen_am
) k;

-- ---------------------------------------------------------------------
-- Anwendungsfall 11: Zeitabweichung je Arbeitsplatz über abgeschlossene Aufträge
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_zeitabweichung_arbeitsplatz AS
SELECT
  ap.nummer            AS arbeitsplatz_nummer,
  ap.bezeichnung       AS arbeitsplatz,
  COUNT(*)             AS arbeitsgaenge,
  SUM(z.soll_zeit_min) AS soll_zeit_min,
  SUM(z.ist_zeit_min)  AS ist_zeit_min,
  ROUND(100 * (SUM(z.ist_zeit_min) - SUM(z.soll_zeit_min)) / NULLIF(SUM(z.soll_zeit_min), 0), 1) AS abweichung_prozent
FROM v_arbeitsgang_zeit z
JOIN fertigungsauftrag fa ON fa.id = z.fertigungsauftrag_id AND fa.status IN ('fertig', 'nachkalkuliert')
JOIN arbeitsplatz ap      ON ap.id = z.arbeitsplatz_id
GROUP BY ap.id, ap.nummer, ap.bezeichnung;

-- ---------------------------------------------------------------------
-- Anwendungsfall 11: Unterbrechungen einzeln (für Auswertungen mit Zeitraum)
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_unterbrechung AS
SELECT
  u.id                 AS unterbrechung_id,
  u.beginn,
  u.ende,
  TIMESTAMPDIFF(MINUTE, u.beginn, u.ende) AS dauer_min,
  ug.bezeichnung       AS grund,
  ug.geplant,
  fa.auftragsnummer,
  ag.ag_nummer,
  ap.bezeichnung       AS arbeitsplatz,
  CONCAT(b.vorname, ' ', b.nachname) AS werker
FROM unterbrechung u
JOIN unterbrechungsgrund ug ON ug.id = u.unterbrechungsgrund_id
JOIN arbeitsgang ag         ON ag.id = u.arbeitsgang_id
JOIN fertigungsauftrag fa   ON fa.id = ag.fertigungsauftrag_id
JOIN arbeitsplatz ap        ON ap.id = ag.arbeitsplatz_id
JOIN benutzer b             ON b.id = u.benutzer_id;

-- Anwendungsfall 11: Wo geht Zeit verloren? Abgeschlossene Unterbrechungen nach Grund
CREATE OR REPLACE VIEW v_unterbrechung_auswertung AS
SELECT
  ug.bezeichnung       AS grund,
  ug.geplant,
  COUNT(u.unterbrechung_id)          AS anzahl,
  COALESCE(SUM(u.dauer_min), 0)      AS dauer_min,
  (SELECT x.arbeitsplatz FROM v_unterbrechung x
   WHERE x.grund = ug.bezeichnung AND x.ende IS NOT NULL
   GROUP BY x.arbeitsplatz ORDER BY COUNT(*) DESC, SUM(x.dauer_min) DESC LIMIT 1) AS haeufigster_arbeitsplatz
FROM unterbrechungsgrund ug
LEFT JOIN v_unterbrechung u ON u.grund = ug.bezeichnung AND u.ende IS NOT NULL
GROUP BY ug.id, ug.bezeichnung, ug.geplant;

-- ---------------------------------------------------------------------
-- Übernahme von Abweichungen in die neue Kalkulation (Rückmeldung vom 01.10.2026)
-- Vergleicht die Soll-Zeiten im Arbeitsplan mit dem Durchschnitt der Ist-Zeiten
-- aus abgeschlossenen Aufträgen. Vorschlag erst ab mehr als 10 % Abweichung.
--   Ø Ist-Rüstzeit  = Mittelwert der Rüstzeit je Auftrag
--   Ø Ist-Stückzeit = Bearbeitungszeit ÷ produzierte Menge (Gut + Ausschuss)
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_sollzeit_vorschlag AS
SELECT
  s.*,
  ROUND(100 * (s.ist_ruestzeit_min  - s.soll_ruestzeit_min)  / NULLIF(s.soll_ruestzeit_min, 0), 1)  AS abweichung_ruesten_prozent,
  ROUND(100 * (s.ist_stueckzeit_min - s.soll_stueckzeit_min) / NULLIF(s.soll_stueckzeit_min, 0), 1) AS abweichung_stueck_prozent,
  CASE WHEN s.soll_ruestzeit_min > 0
        AND ABS(s.ist_ruestzeit_min - s.soll_ruestzeit_min) > 0.10 * s.soll_ruestzeit_min
       THEN ROUND(s.ist_ruestzeit_min, 1) END    AS vorschlag_ruestzeit_min,
  CASE WHEN s.soll_stueckzeit_min > 0
        AND ABS(s.ist_stueckzeit_min - s.soll_stueckzeit_min) > 0.10 * s.soll_stueckzeit_min
       THEN ROUND(s.ist_stueckzeit_min, 2) END   AS vorschlag_stueckzeit_min
FROM (
  SELECT
    pos.id             AS arbeitsplan_position_id,
    ar.artikelnummer,
    pos.ag_nummer,
    pos.bezeichnung    AS arbeitsgang,
    pos.ruestzeit_min  AS soll_ruestzeit_min,
    pos.stueckzeit_min AS soll_stueckzeit_min,
    COUNT(*)           AS anzahl_auftraege,
    AVG(z.ist_ruesten_min)                                              AS ist_ruestzeit_min,
    SUM(z.ist_bearbeiten_min) / NULLIF(SUM(ag.gutmenge + ag.ausschuss), 0) AS ist_stueckzeit_min
  FROM arbeitsplan_position pos
  JOIN artikel ar           ON ar.id = pos.artikel_id
  JOIN arbeitsgang ag       ON ag.arbeitsplan_position_id = pos.id AND ag.status = 'erledigt'
  JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id AND fa.status IN ('fertig', 'nachkalkuliert')
  JOIN v_arbeitsgang_zeit z ON z.arbeitsgang_id = ag.id
  GROUP BY pos.id, ar.artikelnummer, pos.ag_nummer, pos.bezeichnung, pos.ruestzeit_min, pos.stueckzeit_min
) s;
