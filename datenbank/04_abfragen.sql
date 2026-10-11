-- =====================================================================
-- FA-Digital: Beispielabfragen je Anwendungsfall (nur lesend)
-- So holt sich später jede PHP-Seite ihre Daten. Werte wie Arbeitsplatz,
-- Auftrag oder Zeitraum kommen in PHP als Parameter (Prepared Statements).
--
-- Ausführen nach 01 bis 03:  mysql -u root fa_digital < 04_abfragen.sql
-- =====================================================================

USE fa_digital;

-- Anmeldung: Benutzer zur E-Mail laden. Das Passwort prüft PHP mit password_verify().
SELECT id, vorname, nachname, rolle, passwort_hash
FROM benutzer
WHERE email = 'jonas.wolff@fa-digital.de' AND aktiv = 1;

-- AF 1: Arbeitsvorrat am Arbeitsplatz CNC-Drehmaschine
SELECT auftragsnummer, ag_nummer, arbeitsgang, artikel, menge, soll_zeit_min, liefertermin, bereitschaft, wartet_auf_ag
FROM v_arbeitsvorrat
WHERE arbeitsplatz_nummer = 'AP-20'
ORDER BY FIELD(bereitschaft, 'laeuft', 'unterbrochen', 'bereit', 'wartet'), liefertermin;

-- AF 2: Laufender Arbeitsgang am Terminal mit verbrauchter Soll-Zeit
SELECT auftragsnummer, ag_nummer, arbeitsgang, soll_zeit_min, ist_ruesten_min, ist_bearbeiten_min, ist_zeit_min,
       ROUND(100 * ist_zeit_min / soll_zeit_min) AS verbraucht_prozent
FROM v_arbeitsgang_zeit
WHERE arbeitsplatz_id = (SELECT id FROM arbeitsplatz WHERE nummer = 'AP-20') AND status = 'laeuft';

-- AF 3: Auswahlliste der Unterbrechungsgründe
SELECT id, bezeichnung FROM unterbrechungsgrund WHERE aktiv = 1 ORDER BY id;

-- AF 4: Checkliste des laufenden Arbeitsgangs FA-1002 · AG 20
SELECT reihenfolge, pruefpunkt, art, sollwert, untere_grenze, obere_grenze, einheit, istwert, in_toleranz, ergebnis
FROM v_checkliste
WHERE auftragsnummer = 'FA-1002' AND ag_nummer = 20
ORDER BY reihenfolge;

-- AF 5: Darf der Arbeitsgang fertig gemeldet werden? Nur wenn kein Prüfpunkt mehr offen ist.
SELECT ag.id, COUNT(pp.id) AS offene_pruefpunkte, COUNT(pp.id) = 0 AS fertigmeldung_erlaubt
FROM arbeitsgang ag
JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id
LEFT JOIN pruefpunkt pp   ON pp.arbeitsgang_id = ag.id AND pp.ergebnis = 'offen'
WHERE fa.auftragsnummer = 'FA-1002' AND ag.ag_nummer = 20
GROUP BY ag.id;

-- AF 6: Alle Zeitbuchungen eines Auftrags, auch ungültige, für die Nachvollziehbarkeit
SELECT ag.ag_nummer, CONCAT(b.vorname, ' ', b.nachname) AS werker, zb.taetigkeit, zb.beginn, zb.ende,
       TIMESTAMPDIFF(MINUTE, zb.beginn, COALESCE(zb.ende, NOW())) AS dauer_min,
       zb.quelle, zb.gueltig, zb.begruendung, CONCAT(e.vorname, ' ', e.nachname) AS erfasst_von, zb.erfasst_am
FROM zeitbuchung zb
JOIN arbeitsgang ag       ON ag.id = zb.arbeitsgang_id
JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id
JOIN benutzer b           ON b.id = zb.benutzer_id
JOIN benutzer e           ON e.id = zb.erfasst_von
WHERE fa.auftragsnummer = 'FA-1002'
ORDER BY zb.beginn, zb.id;

-- AF 7: Hallenansicht und letzte Buchungen
SELECT arbeitsplatz_nummer, arbeitsplatz, zustand, auftragsnummer, ag_nummer, werker, laufende_taetigkeit,
       ist_zeit_min, soll_zeit_min, verbraucht_prozent, ampel, unterbrechungsgrund, unterbrochen_min
FROM v_hallenansicht
ORDER BY arbeitsplatz_nummer;

SELECT zeitpunkt, mitarbeiter, arbeitsplatz, auftragsnummer, ag_nummer, buchung
FROM v_letzte_buchungen
WHERE zeitpunkt >= NOW() - INTERVAL 1 DAY
ORDER BY zeitpunkt DESC
LIMIT 10;

-- Schlanke Meister-Ansicht: die Aufträge in der Werkstatt, dringendste zuerst
SELECT * FROM v_meister_auftraege
ORDER BY verzug DESC, liefertermin
LIMIT 5;

-- AF 8: Auftragsübersicht, gefiltert nach Status
SELECT auftragsnummer, artikel, menge, liefertermin, verzug, status,
       CONCAT(ag_erledigt, ' von ', ag_gesamt) AS fortschritt, soll_zeit_min, ist_zeit_min
FROM v_auftragsuebersicht
WHERE status = 'in_arbeit'
ORDER BY liefertermin;

-- AF 9: Daten für das Werkszertifikat von FA-1001
SELECT * FROM v_zertifikat_status WHERE auftragsnummer = 'FA-1001';

SELECT ag_nummer, arbeitsgang, pruefpunkt, sollwert, untere_grenze, obere_grenze, einheit, istwert, ergebnis, geprueft_von, geprueft_am
FROM v_checkliste
WHERE auftragsnummer = 'FA-1001'
ORDER BY ag_nummer, reihenfolge;

-- AF 10: Nachkalkulation von FA-1001, je Arbeitsgang und gesamt
SELECT ag_nummer, arbeitsgang, arbeitsplatz, stundensatz, soll_ruesten_min, ist_ruesten_min,
       soll_bearbeiten_min, ist_bearbeiten_min, soll_kosten, ist_kosten, abweichung_prozent, ampel
FROM v_nachkalkulation_arbeitsgang
WHERE auftragsnummer = 'FA-1001'
ORDER BY ag_nummer;

SELECT auftragsnummer, artikel, soll_zeit_min, ist_zeit_min, soll_kosten, ist_kosten, abweichung_prozent, ampel,
       gutmenge, ausschuss, soll_kosten_je_stueck, ist_kosten_je_gutstueck
FROM v_nachkalkulation_auftrag
WHERE auftragsnummer = 'FA-1001';

-- AF 11: Auswertung über alle abgeschlossenen Aufträge der letzten 30 Tage
SELECT auftragsnummer, artikel, gutmenge, ausschuss, DATE(abgeschlossen_am) AS abschluss,
       soll_kosten, ist_kosten, abweichung_prozent, ampel, soll_kosten_je_stueck, ist_kosten_je_gutstueck
FROM v_nachkalkulation_auftrag
WHERE status IN ('fertig', 'nachkalkuliert') AND abgeschlossen_am >= NOW() - INTERVAL 30 DAY
ORDER BY abgeschlossen_am;

SELECT * FROM v_zeitabweichung_arbeitsplatz ORDER BY arbeitsplatz_nummer;

-- Unterbrechungen nach Grund im Zeitraum, ungeplante zuerst, mit Anteil
SELECT grund, COUNT(*) AS anzahl, SUM(dauer_min) AS dauer_min,
       ROUND(100 * SUM(dauer_min) / SUM(SUM(dauer_min)) OVER (PARTITION BY geplant)) AS anteil_prozent
FROM v_unterbrechung
WHERE ende IS NOT NULL AND beginn >= NOW() - INTERVAL 30 DAY
GROUP BY grund, geplant
ORDER BY geplant, dauer_min DESC;

-- Übernahme von Abweichungen in die neue Kalkulation: aktuelle Vorschläge
SELECT artikelnummer, ag_nummer, arbeitsgang, anzahl_auftraege,
       soll_ruestzeit_min, ROUND(ist_ruestzeit_min, 1) AS ist_ruestzeit_min, vorschlag_ruestzeit_min,
       soll_stueckzeit_min, ROUND(ist_stueckzeit_min, 2) AS ist_stueckzeit_min, vorschlag_stueckzeit_min
FROM v_sollzeit_vorschlag
WHERE vorschlag_ruestzeit_min IS NOT NULL OR vorschlag_stueckzeit_min IS NOT NULL
ORDER BY artikelnummer, ag_nummer;

-- AF 12: Benutzerliste für die Verwaltung
SELECT personalnummer, CONCAT(vorname, ' ', nachname) AS name, email, rolle, aktiv
FROM benutzer
ORDER BY aktiv DESC, nachname;
