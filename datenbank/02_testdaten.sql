-- =====================================================================
-- FA-Digital: Testdaten
-- Erzeugt mit hilfsskripte/erzeuge-testdaten.js, passend zu den Beispielen im Prototyp.
-- Alle Zeitpunkte sind relativ zum Importzeitpunkt.
--
-- Ausführen nach 01_schema.sql:  mysql -u root fa_digital < 02_testdaten.sql
-- Anmeldung für alle Test-Benutzer: Passwort „fa-digital“ (nur für die Entwicklung)
-- =====================================================================

USE fa_digital;
SET NAMES utf8mb4;
SET @jetzt = NOW();
SET @heute = CURDATE();
SET @hash = '$2y$10$1wYaHHQfaHvsAPyKj2guhuulaJuXXhGcEZaGLN0Aq6qxz7agnOw9m';

-- Benutzer
INSERT INTO benutzer (personalnummer, vorname, nachname, email, passwort_hash, rolle, aktiv) VALUES
  ('1000', 'Sven', 'Petersen', 'sven.petersen@fa-digital.de', @hash, 'admin', 1),
  ('1001', 'Anna', 'Becker', 'anna.becker@fa-digital.de', @hash, 'arbeitsvorbereitung', 1),
  ('1002', 'Lena', 'Hoffmann', 'lena.hoffmann@fa-digital.de', @hash, 'meister', 1),
  ('1010', 'Tobias', 'Krüger', 'tobias.krueger@fa-digital.de', @hash, 'controlling', 1),
  ('1042', 'Jonas', 'Wolff', 'jonas.wolff@fa-digital.de', @hash, 'werker', 1),
  ('1043', 'Mehmet', 'Yilmaz', 'mehmet.yilmaz@fa-digital.de', @hash, 'werker', 1),
  ('1045', 'Paul', 'Schneider', 'paul.schneider@fa-digital.de', @hash, 'werker', 1),
  ('1044', 'Katrin', 'Wagner', 'katrin.wagner@fa-digital.de', @hash, 'werker', 0);
SET @sven = (SELECT id FROM benutzer WHERE personalnummer = '1000');
SET @anna = (SELECT id FROM benutzer WHERE personalnummer = '1001');
SET @lena = (SELECT id FROM benutzer WHERE personalnummer = '1002');
SET @tobias = (SELECT id FROM benutzer WHERE personalnummer = '1010');
SET @jonas = (SELECT id FROM benutzer WHERE personalnummer = '1042');
SET @mehmet = (SELECT id FROM benutzer WHERE personalnummer = '1043');
SET @paul = (SELECT id FROM benutzer WHERE personalnummer = '1045');
SET @katrin = (SELECT id FROM benutzer WHERE personalnummer = '1044');

-- Arbeitsplätze: Stationen vom Blech-Rohteil bis zum Versand
INSERT INTO arbeitsplatz (nummer, bezeichnung, kostenstelle, stundensatz, aktiv) VALUES
  ('AP-10', 'Laserschneidanlage', '4100', 110.00, 1),
  ('AP-20', 'Abkantpresse', '4200', 75.00, 1),
  ('AP-30', 'Schweißplatz', '4300', 65.00, 1),
  ('AP-40', 'CNC-Drehmaschine', '4400', 85.00, 1),
  ('AP-50', 'CNC-Fräsmaschine', '4500', 95.00, 1),
  ('AP-60', 'Lackieranlage', '4600', 70.00, 1),
  ('AP-70', 'Versandplatz', '4700', 40.00, 1),
  ('AP-90', 'Konventionelle Drehmaschine', '4410', 60.00, 0);

-- Artikel: Verkleidungsteile für Zerspanungsmaschinen (V) und Zerspanteile
INSERT INTO artikel (artikelnummer, bezeichnung) VALUES
  ('V-410', 'Seitenverkleidung Fräszentrum'),
  ('V-420', 'Späneschutzwanne'),
  ('V-430', 'Schutztür mit Sichtfenster'),
  ('F-310', 'Flansch DN80'),
  ('W-200', 'Antriebswelle Ø40');

-- Arbeitspläne: Soll-Rüstzeit je Auftrag und Soll-Stückzeit in Minuten
INSERT INTO arbeitsplan_position (artikel_id, ag_nummer, bezeichnung, arbeitsplatz_id, ruestzeit_min, stueckzeit_min)
SELECT a.id, v.ag, v.bez, p.id, v.ruest, v.stueck
FROM (
  SELECT 'V-410' AS art, 10 AS ag, 'Auslasern' AS bez, 'AP-10' AS ap, 15 AS ruest, 2.5 AS stueck
  UNION ALL SELECT 'V-410' AS art, 20 AS ag, 'Kanten' AS bez, 'AP-20' AS ap, 20 AS ruest, 3 AS stueck
  UNION ALL SELECT 'V-410' AS art, 30 AS ag, 'Lackieren' AS bez, 'AP-60' AS ap, 25 AS ruest, 4 AS stueck
  UNION ALL SELECT 'V-410' AS art, 40 AS ag, 'Verpacken und versenden' AS bez, 'AP-70' AS ap, 10 AS ruest, 0.5 AS stueck
  UNION ALL SELECT 'V-420' AS art, 10 AS ag, 'Auslasern' AS bez, 'AP-10' AS ap, 15 AS ruest, 3 AS stueck
  UNION ALL SELECT 'V-420' AS art, 20 AS ag, 'Kanten zur Wanne' AS bez, 'AP-20' AS ap, 25 AS ruest, 6 AS stueck
  UNION ALL SELECT 'V-420' AS art, 30 AS ag, 'Ecken verschweißen' AS bez, 'AP-30' AS ap, 20 AS ruest, 12 AS stueck
  UNION ALL SELECT 'V-420' AS art, 40 AS ag, 'Lackieren' AS bez, 'AP-60' AS ap, 25 AS ruest, 5 AS stueck
  UNION ALL SELECT 'V-420' AS art, 50 AS ag, 'Verpacken und versenden' AS bez, 'AP-70' AS ap, 10 AS ruest, 1 AS stueck
  UNION ALL SELECT 'V-430' AS art, 10 AS ag, 'Auslasern' AS bez, 'AP-10' AS ap, 15 AS ruest, 4 AS stueck
  UNION ALL SELECT 'V-430' AS art, 20 AS ag, 'Kanten' AS bez, 'AP-20' AS ap, 20 AS ruest, 5 AS stueck
  UNION ALL SELECT 'V-430' AS art, 30 AS ag, 'Scharniere anschweißen' AS bez, 'AP-30' AS ap, 15 AS ruest, 8 AS stueck
  UNION ALL SELECT 'V-430' AS art, 40 AS ag, 'Fenstersitz fräsen' AS bez, 'AP-50' AS ap, 30 AS ruest, 6 AS stueck
  UNION ALL SELECT 'V-430' AS art, 50 AS ag, 'Lackieren' AS bez, 'AP-60' AS ap, 25 AS ruest, 5 AS stueck
  UNION ALL SELECT 'V-430' AS art, 60 AS ag, 'Verpacken und versenden' AS bez, 'AP-70' AS ap, 10 AS ruest, 1 AS stueck
  UNION ALL SELECT 'F-310' AS art, 10 AS ag, 'Ronde auslasern' AS bez, 'AP-10' AS ap, 10 AS ruest, 2 AS stueck
  UNION ALL SELECT 'F-310' AS art, 20 AS ag, 'Drehen' AS bez, 'AP-40' AS ap, 25 AS ruest, 3 AS stueck
  UNION ALL SELECT 'F-310' AS art, 30 AS ag, 'Lochkreis fräsen' AS bez, 'AP-50' AS ap, 40 AS ruest, 5 AS stueck
  UNION ALL SELECT 'F-310' AS art, 40 AS ag, 'Verpacken und versenden' AS bez, 'AP-70' AS ap, 10 AS ruest, 0.5 AS stueck
  UNION ALL SELECT 'W-200' AS art, 10 AS ag, 'Drehen' AS bez, 'AP-40' AS ap, 30 AS ruest, 4 AS stueck
  UNION ALL SELECT 'W-200' AS art, 20 AS ag, 'Passfedernut fräsen' AS bez, 'AP-50' AS ap, 20 AS ruest, 2 AS stueck
  UNION ALL SELECT 'W-200' AS art, 30 AS ag, 'Verpacken und versenden' AS bez, 'AP-70' AS ap, 10 AS ruest, 0.5 AS stueck
) v
JOIN artikel a      ON a.artikelnummer = v.art
JOIN arbeitsplatz p ON p.nummer = v.ap;

-- Zertifikatsarten: nach DIN EN 10204 und eigene Prüfprotokolle der Arbeitsvorbereitung
INSERT INTO zertifikatsart (kuerzel, bezeichnung, beschreibung) VALUES
  ('2.1', 'Werksbescheinigung 2.1', 'Bestätigung, dass die Lieferung der Bestellung entspricht, ohne Prüfergebnisse'),
  ('2.2', 'Werkszeugnis 2.2', 'Bestätigung mit Ergebnissen nichtspezifischer Prüfungen'),
  ('3.1', 'Abnahmeprüfzeugnis 3.1', 'Ergebnisse spezifischer Prüfungen, bestätigt durch einen von der Fertigung unabhängigen Abnahmebeauftragten'),
  ('LP', 'Lackprüfprotokoll', 'Eigenes Protokoll für lackierte Verkleidungsteile: Schichtdicke und Oberfläche'),
  ('DP', 'Dichtheitsprotokoll', 'Eigenes Protokoll für Späneschutzwannen: Wasserprobe der Schweißnähte');

-- Unterbrechungsgründe am Werker-Terminal
INSERT INTO unterbrechungsgrund (bezeichnung, geplant) VALUES
  ('Pause', 1),
  ('Material fehlt', 0),
  ('Maschinenstörung', 0),
  ('Werkzeug fehlt', 0),
  ('Sonstiges', 0);

-- Prüfmerkmale: Vorlage der Checklisten. Mit Zertifikatsart = nur, wenn der Kunde dieses Zertifikat fordert
INSERT INTO pruefmerkmal (arbeitsplan_position_id, reihenfolge, bezeichnung, art, sollwert, untere_grenze, obere_grenze, einheit, zertifikatsart_id)
SELECT pos.id, v.nr, v.bez, v.art, v.soll, v.unten, v.oben, v.einheit, za.id
FROM (
  SELECT 'V-410' AS artikel, 20 AS ag, 1 AS nr, 'Kantwinkel 90° ±1°' AS bez, 'messwert' AS art, 90 AS soll, 89 AS unten, 91 AS oben, '°' AS einheit, NULL AS zert
  UNION ALL SELECT 'V-410' AS artikel, 30 AS ag, 1 AS nr, 'Sichtprüfung Lackoberfläche' AS bez, 'sichtpruefung' AS art, NULL AS soll, NULL AS unten, NULL AS oben, NULL AS einheit, NULL AS zert
  UNION ALL SELECT 'V-410' AS artikel, 30 AS ag, 2 AS nr, 'Schichtdicke Lack' AS bez, 'messwert' AS art, 80 AS soll, 60 AS unten, 100 AS oben, 'µm' AS einheit, 'LP' AS zert
  UNION ALL SELECT 'V-420' AS artikel, 20 AS ag, 1 AS nr, 'Innenmaß Wanne 600 ±1' AS bez, 'messwert' AS art, 600 AS soll, 599 AS unten, 601 AS oben, 'mm' AS einheit, NULL AS zert
  UNION ALL SELECT 'V-420' AS artikel, 30 AS ag, 1 AS nr, 'Sichtprüfung Schweißnähte' AS bez, 'sichtpruefung' AS art, NULL AS soll, NULL AS unten, NULL AS oben, NULL AS einheit, NULL AS zert
  UNION ALL SELECT 'V-420' AS artikel, 30 AS ag, 2 AS nr, 'Dichtheit: Wasserprobe 30 min' AS bez, 'sichtpruefung' AS art, NULL AS soll, NULL AS unten, NULL AS oben, NULL AS einheit, 'DP' AS zert
  UNION ALL SELECT 'V-420' AS artikel, 40 AS ag, 1 AS nr, 'Schichtdicke Lack' AS bez, 'messwert' AS art, 80 AS soll, 60 AS unten, 100 AS oben, 'µm' AS einheit, 'LP' AS zert
  UNION ALL SELECT 'V-430' AS artikel, 20 AS ag, 1 AS nr, 'Kantwinkel 90° ±1°' AS bez, 'messwert' AS art, 90 AS soll, 89 AS unten, 91 AS oben, '°' AS einheit, NULL AS zert
  UNION ALL SELECT 'V-430' AS artikel, 40 AS ag, 1 AS nr, 'Tiefe Fenstersitz 5 ±0,1' AS bez, 'messwert' AS art, 5 AS soll, 4.9 AS unten, 5.1 AS oben, 'mm' AS einheit, NULL AS zert
  UNION ALL SELECT 'V-430' AS artikel, 50 AS ag, 1 AS nr, 'Schichtdicke Lack' AS bez, 'messwert' AS art, 80 AS soll, 60 AS unten, 100 AS oben, 'µm' AS einheit, 'LP' AS zert
  UNION ALL SELECT 'F-310' AS artikel, 20 AS ag, 1 AS nr, 'Außendurchmesser Ø200 ±0,1' AS bez, 'messwert' AS art, 200 AS soll, 199.9 AS unten, 200.1 AS oben, 'mm' AS einheit, NULL AS zert
  UNION ALL SELECT 'F-310' AS artikel, 20 AS ag, 2 AS nr, 'Planlauf Dichtfläche' AS bez, 'messwert' AS art, 0 AS soll, 0 AS unten, 0.05 AS oben, 'mm' AS einheit, '2.2' AS zert
  UNION ALL SELECT 'F-310' AS artikel, 30 AS ag, 1 AS nr, 'Lochkreis Ø160 ±0,05' AS bez, 'messwert' AS art, 160 AS soll, 159.95 AS unten, 160.05 AS oben, 'mm' AS einheit, NULL AS zert
  UNION ALL SELECT 'W-200' AS artikel, 10 AS ag, 1 AS nr, 'Durchmesser Lagersitz Ø40 h6' AS bez, 'messwert' AS art, 40 AS soll, 39.984 AS unten, 40 AS oben, 'mm' AS einheit, NULL AS zert
  UNION ALL SELECT 'W-200' AS artikel, 10 AS ag, 2 AS nr, 'Gesamtlänge 250 ±0,2' AS bez, 'messwert' AS art, 250 AS soll, 249.8 AS unten, 250.2 AS oben, 'mm' AS einheit, NULL AS zert
  UNION ALL SELECT 'W-200' AS artikel, 10 AS ag, 3 AS nr, 'Rundlauf Lagersitz' AS bez, 'messwert' AS art, 0 AS soll, 0 AS unten, 0.02 AS oben, 'mm' AS einheit, '2.2' AS zert
  UNION ALL SELECT 'W-200' AS artikel, 10 AS ag, 4 AS nr, 'Rauheit Lagersitz Ra' AS bez, 'messwert' AS art, 0.4 AS soll, 0 AS unten, 0.8 AS oben, 'µm' AS einheit, '2.2' AS zert
  UNION ALL SELECT 'W-200' AS artikel, 20 AS ag, 1 AS nr, 'Nutbreite 12 ±0,05' AS bez, 'messwert' AS art, 12 AS soll, 11.95 AS unten, 12.05 AS oben, 'mm' AS einheit, NULL AS zert
) v
JOIN artikel a                ON a.artikelnummer = v.artikel
JOIN arbeitsplan_position pos ON pos.artikel_id = a.id AND pos.ag_nummer = v.ag
LEFT JOIN zertifikatsart za   ON za.kuerzel = v.zert;

-- Fertigungsaufträge
INSERT INTO fertigungsauftrag (auftragsnummer, artikel_id, menge, kunde, liefertermin, status, angelegt_von, angelegt_am, freigegeben_von, freigegeben_am, abgeschlossen_von, abgeschlossen_am, nachkalkuliert_am) VALUES
  ('FA-0997', (SELECT id FROM artikel WHERE artikelnummer = 'F-310'), 16, 'Hansa Pumpen AG', @heute + INTERVAL 9 DAY, 'storniert', @anna, @jetzt - INTERVAL 13260 MINUTE, NULL, NULL, NULL, NULL, NULL),
  ('FA-0998', (SELECT id FROM artikel WHERE artikelnummer = 'V-410'), 60, 'Nordwerk Maschinenbau GmbH', @heute - INTERVAL 8 DAY, 'nachkalkuliert', @anna, @jetzt - INTERVAL 20660 MINUTE, @anna, @jetzt - INTERVAL 20610 MINUTE, @lena, @jetzt - INTERVAL 14600 MINUTE, @jetzt - INTERVAL 13260 MINUTE),
  ('FA-0999', (SELECT id FROM artikel WHERE artikelnummer = 'F-310'), 20, 'Hansa Pumpen AG', @heute - INTERVAL 2 DAY, 'nachkalkuliert', @anna, @jetzt - INTERVAL 14900 MINUTE, @anna, @jetzt - INTERVAL 14850 MINUTE, @lena, @jetzt - INTERVAL 7400 MINUTE, @jetzt - INTERVAL 6060 MINUTE),
  ('FA-1000', (SELECT id FROM artikel WHERE artikelnummer = 'V-420'), 10, 'Baltic Werkzeugmaschinen GmbH', @heute - INTERVAL 3 DAY, 'in_arbeit', @anna, @jetzt - INTERVAL 12020 MINUTE, @anna, @jetzt - INTERVAL 10530 MINUTE, NULL, NULL, NULL),
  ('FA-1001', (SELECT id FROM artikel WHERE artikelnummer = 'V-410'), 50, 'Nordwerk Maschinenbau GmbH', @heute + INTERVAL 4 DAY, 'fertig', @anna, @jetzt - INTERVAL 9140 MINUTE, @anna, @jetzt - INTERVAL 9090 MINUTE, @lena, @jetzt - INTERVAL 30 MINUTE, NULL),
  ('FA-1002', (SELECT id FROM artikel WHERE artikelnummer = 'F-310'), 30, 'Hansa Pumpen AG', @heute + INTERVAL 8 DAY, 'in_arbeit', @anna, @jetzt - INTERVAL 6260 MINUTE, @anna, @jetzt - INTERVAL 6210 MINUTE, NULL, NULL, NULL),
  ('FA-1003', (SELECT id FROM artikel WHERE artikelnummer = 'V-430'), 12, 'Kieler Werft Service', @heute + INTERVAL 11 DAY, 'in_arbeit', @anna, @jetzt - INTERVAL 4820 MINUTE, @anna, @jetzt - INTERVAL 4770 MINUTE, NULL, NULL, NULL),
  ('FA-1004', (SELECT id FROM artikel WHERE artikelnummer = 'F-310'), 40, 'Hansa Pumpen AG', @heute + INTERVAL 16 DAY, 'in_arbeit', @anna, @jetzt - INTERVAL 3380 MINUTE, @anna, @jetzt - INTERVAL 50 MINUTE, NULL, NULL, NULL),
  ('FA-1005', (SELECT id FROM artikel WHERE artikelnummer = 'V-420'), 8, 'Baltic Werkzeugmaschinen GmbH', @heute + INTERVAL 18 DAY, 'freigegeben', @anna, @jetzt - INTERVAL 1740 MINUTE, @anna, @jetzt - INTERVAL 120 MINUTE, NULL, NULL, NULL),
  ('FA-1006', (SELECT id FROM artikel WHERE artikelnummer = 'W-200'), 120, 'Kieler Werft Service', @heute + INTERVAL 23 DAY, 'angelegt', @anna, @jetzt - INTERVAL 10 MINUTE, NULL, NULL, NULL, NULL, NULL);

-- Geforderte Werkszertifikate (werden bei der Auftragsanlage festgelegt)
INSERT INTO auftrag_zertifikat (fertigungsauftrag_id, zertifikatsart_id, status, erstellt_am, freigegeben_von, freigegeben_am, dateiname) VALUES
  ((SELECT id FROM fertigungsauftrag WHERE auftragsnummer = 'FA-0998'), (SELECT id FROM zertifikatsart WHERE kuerzel = 'LP'), 'freigegeben', @jetzt - INTERVAL 14580 MINUTE, @lena, @jetzt - INTERVAL 14550 MINUTE, 'WZ_FA-0998_LP.pdf'),
  ((SELECT id FROM fertigungsauftrag WHERE auftragsnummer = 'FA-0999'), (SELECT id FROM zertifikatsart WHERE kuerzel = '2.1'), 'freigegeben', @jetzt - INTERVAL 7380 MINUTE, @lena, @jetzt - INTERVAL 7350 MINUTE, 'WZ_FA-0999_2.1.pdf'),
  ((SELECT id FROM fertigungsauftrag WHERE auftragsnummer = 'FA-1000'), (SELECT id FROM zertifikatsart WHERE kuerzel = 'DP'), 'gefordert', NULL, NULL, NULL, NULL),
  ((SELECT id FROM fertigungsauftrag WHERE auftragsnummer = 'FA-1001'), (SELECT id FROM zertifikatsart WHERE kuerzel = 'LP'), 'gefordert', NULL, NULL, NULL, NULL),
  ((SELECT id FROM fertigungsauftrag WHERE auftragsnummer = 'FA-1002'), (SELECT id FROM zertifikatsart WHERE kuerzel = '2.2'), 'gefordert', NULL, NULL, NULL, NULL),
  ((SELECT id FROM fertigungsauftrag WHERE auftragsnummer = 'FA-1004'), (SELECT id FROM zertifikatsart WHERE kuerzel = '3.1'), 'gefordert', NULL, NULL, NULL, NULL),
  ((SELECT id FROM fertigungsauftrag WHERE auftragsnummer = 'FA-1006'), (SELECT id FROM zertifikatsart WHERE kuerzel = '2.2'), 'gefordert', NULL, NULL, NULL, NULL);

-- Arbeitsgänge: Kopie des Arbeitsplans je Auftrag mit dem Stundensatz bei der Anlage.
-- So entstehen die Arbeitsgänge auch später in der Anwendung beim Anlegen eines Auftrags.
INSERT INTO arbeitsgang (fertigungsauftrag_id, arbeitsplan_position_id, ag_nummer, bezeichnung, arbeitsplatz_id,
                         soll_ruestzeit_min, soll_stueckzeit_min, stundensatz)
SELECT fa.id, pos.id, pos.ag_nummer, pos.bezeichnung, pos.arbeitsplatz_id, pos.ruestzeit_min, pos.stueckzeit_min, ap.stundensatz
FROM fertigungsauftrag fa
JOIN arbeitsplan_position pos ON pos.artikel_id = fa.artikel_id
JOIN arbeitsplatz ap          ON ap.id = pos.arbeitsplatz_id
ORDER BY fa.id, pos.ag_nummer;

-- Checklisten: Kopie der Prüfmerkmale je Arbeitsgang.
-- Prüfmerkmale mit Zertifikatsart kommen nur dazu, wenn der Auftrag dieses Zertifikat fordert.
INSERT INTO pruefpunkt (arbeitsgang_id, pruefmerkmal_id, reihenfolge, bezeichnung, art, sollwert, untere_grenze, obere_grenze, einheit)
SELECT ag.id, pm.id, pm.reihenfolge, pm.bezeichnung, pm.art, pm.sollwert, pm.untere_grenze, pm.obere_grenze, pm.einheit
FROM arbeitsgang ag
JOIN pruefmerkmal pm ON pm.arbeitsplan_position_id = ag.arbeitsplan_position_id
WHERE pm.zertifikatsart_id IS NULL
   OR pm.zertifikatsart_id IN (SELECT az.zertifikatsart_id FROM auftrag_zertifikat az
                               WHERE az.fertigungsauftrag_id = ag.fertigungsauftrag_id)
ORDER BY ag.id, pm.reihenfolge;

-- Zeitbuchungen und Unterbrechungen je Arbeitsgang

-- FA-0998 · AG 10
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-0998' AND ag.ag_nummer = 10);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'ruesten', @jetzt - INTERVAL 19120 MINUTE, @jetzt - INTERVAL 19105 MINUTE, @mehmet, @jetzt - INTERVAL 19120 MINUTE);
INSERT INTO unterbrechung (arbeitsgang_id, benutzer_id, unterbrechungsgrund_id, beginn, ende) VALUES (@ag, @mehmet, (SELECT id FROM unterbrechungsgrund WHERE bezeichnung = 'Material fehlt'), @jetzt - INTERVAL 19105 MINUTE, @jetzt - INTERVAL 19050 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'bearbeiten', @jetzt - INTERVAL 19050 MINUTE, @jetzt - INTERVAL 18910 MINUTE, @mehmet, @jetzt - INTERVAL 19050 MINUTE);
UPDATE arbeitsgang SET status = 'erledigt', gutmenge = 60, ausschuss = 0, fertig_gemeldet_von = @mehmet, fertig_gemeldet_am = @jetzt - INTERVAL 18910 MINUTE WHERE id = @ag;

-- FA-0998 · AG 20
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-0998' AND ag.ag_nummer = 20);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @jonas, 'ruesten', @jetzt - INTERVAL 17700 MINUTE, @jetzt - INTERVAL 17674 MINUTE, @jonas, @jetzt - INTERVAL 17700 MINUTE);
INSERT INTO unterbrechung (arbeitsgang_id, benutzer_id, unterbrechungsgrund_id, beginn, ende) VALUES (@ag, @jonas, (SELECT id FROM unterbrechungsgrund WHERE bezeichnung = 'Werkzeug fehlt'), @jetzt - INTERVAL 17674 MINUTE, @jetzt - INTERVAL 17644 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @jonas, 'bearbeiten', @jetzt - INTERVAL 17644 MINUTE, @jetzt - INTERVAL 17544 MINUTE, @jonas, @jetzt - INTERVAL 17644 MINUTE);
INSERT INTO unterbrechung (arbeitsgang_id, benutzer_id, unterbrechungsgrund_id, beginn, ende) VALUES (@ag, @jonas, (SELECT id FROM unterbrechungsgrund WHERE bezeichnung = 'Maschinenstörung'), @jetzt - INTERVAL 17544 MINUTE, @jetzt - INTERVAL 17519 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @jonas, 'bearbeiten', @jetzt - INTERVAL 17519 MINUTE, @jetzt - INTERVAL 17424 MINUTE, @jonas, @jetzt - INTERVAL 17519 MINUTE);
UPDATE arbeitsgang SET status = 'erledigt', gutmenge = 60, ausschuss = 0, fertig_gemeldet_von = @jonas, fertig_gemeldet_am = @jetzt - INTERVAL 17424 MINUTE WHERE id = @ag;

-- FA-0998 · AG 30
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-0998' AND ag.ag_nummer = 30);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @paul, 'ruesten', @jetzt - INTERVAL 16240 MINUTE, @jetzt - INTERVAL 16216 MINUTE, @paul, @jetzt - INTERVAL 16240 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @paul, 'bearbeiten', @jetzt - INTERVAL 16216 MINUTE, @jetzt - INTERVAL 16096 MINUTE, @paul, @jetzt - INTERVAL 16216 MINUTE);
INSERT INTO unterbrechung (arbeitsgang_id, benutzer_id, unterbrechungsgrund_id, beginn, ende) VALUES (@ag, @paul, (SELECT id FROM unterbrechungsgrund WHERE bezeichnung = 'Pause'), @jetzt - INTERVAL 16096 MINUTE, @jetzt - INTERVAL 16066 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @paul, 'bearbeiten', @jetzt - INTERVAL 16066 MINUTE, @jetzt - INTERVAL 15936 MINUTE, @paul, @jetzt - INTERVAL 16066 MINUTE);
UPDATE arbeitsgang SET status = 'erledigt', gutmenge = 60, ausschuss = 0, fertig_gemeldet_von = @paul, fertig_gemeldet_am = @jetzt - INTERVAL 15936 MINUTE WHERE id = @ag;

-- FA-0998 · AG 40
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-0998' AND ag.ag_nummer = 40);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @paul, 'ruesten', @jetzt - INTERVAL 15000 MINUTE, @jetzt - INTERVAL 14990 MINUTE, @paul, @jetzt - INTERVAL 15000 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @paul, 'bearbeiten', @jetzt - INTERVAL 14990 MINUTE, @jetzt - INTERVAL 14962 MINUTE, @paul, @jetzt - INTERVAL 14990 MINUTE);
UPDATE arbeitsgang SET status = 'erledigt', gutmenge = 60, ausschuss = 0, fertig_gemeldet_von = @paul, fertig_gemeldet_am = @jetzt - INTERVAL 14962 MINUTE WHERE id = @ag;

-- FA-0999 · AG 10
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-0999' AND ag.ag_nummer = 10);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'ruesten', @jetzt - INTERVAL 13360 MINUTE, @jetzt - INTERVAL 13351 MINUTE, @mehmet, @jetzt - INTERVAL 13360 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'bearbeiten', @jetzt - INTERVAL 13351 MINUTE, @jetzt - INTERVAL 13313 MINUTE, @mehmet, @jetzt - INTERVAL 13351 MINUTE);
UPDATE arbeitsgang SET status = 'erledigt', gutmenge = 20, ausschuss = 0, fertig_gemeldet_von = @mehmet, fertig_gemeldet_am = @jetzt - INTERVAL 13313 MINUTE WHERE id = @ag;

-- FA-0999 · AG 20
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-0999' AND ag.ag_nummer = 20);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @jonas, 'ruesten', @jetzt - INTERVAL 11940 MINUTE, @jetzt - INTERVAL 11913 MINUTE, @jonas, @jetzt - INTERVAL 11940 MINUTE);
INSERT INTO unterbrechung (arbeitsgang_id, benutzer_id, unterbrechungsgrund_id, beginn, ende) VALUES (@ag, @jonas, (SELECT id FROM unterbrechungsgrund WHERE bezeichnung = 'Werkzeug fehlt'), @jetzt - INTERVAL 11913 MINUTE, @jetzt - INTERVAL 11895 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @jonas, 'bearbeiten', @jetzt - INTERVAL 11895 MINUTE, @jetzt - INTERVAL 11830 MINUTE, @jonas, @jetzt - INTERVAL 11895 MINUTE);
UPDATE arbeitsgang SET status = 'erledigt', gutmenge = 20, ausschuss = 0, fertig_gemeldet_von = @jonas, fertig_gemeldet_am = @jetzt - INTERVAL 11830 MINUTE WHERE id = @ag;

-- FA-0999 · AG 30
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-0999' AND ag.ag_nummer = 30);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'ruesten', @jetzt - INTERVAL 10480 MINUTE, @jetzt - INTERVAL 10440 MINUTE, @mehmet, @jetzt - INTERVAL 10480 MINUTE);
INSERT INTO unterbrechung (arbeitsgang_id, benutzer_id, unterbrechungsgrund_id, beginn, ende) VALUES (@ag, @mehmet, (SELECT id FROM unterbrechungsgrund WHERE bezeichnung = 'Maschinenstörung'), @jetzt - INTERVAL 10440 MINUTE, @jetzt - INTERVAL 10370 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'bearbeiten', @jetzt - INTERVAL 10370 MINUTE, @jetzt - INTERVAL 10320 MINUTE, @mehmet, @jetzt - INTERVAL 10370 MINUTE);
INSERT INTO unterbrechung (arbeitsgang_id, benutzer_id, unterbrechungsgrund_id, beginn, ende) VALUES (@ag, @mehmet, (SELECT id FROM unterbrechungsgrund WHERE bezeichnung = 'Pause'), @jetzt - INTERVAL 10320 MINUTE, @jetzt - INTERVAL 10290 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'bearbeiten', @jetzt - INTERVAL 10290 MINUTE, @jetzt - INTERVAL 10260 MINUTE, @mehmet, @jetzt - INTERVAL 10290 MINUTE);
INSERT INTO unterbrechung (arbeitsgang_id, benutzer_id, unterbrechungsgrund_id, beginn, ende) VALUES (@ag, @mehmet, (SELECT id FROM unterbrechungsgrund WHERE bezeichnung = 'Maschinenstörung'), @jetzt - INTERVAL 10260 MINUTE, @jetzt - INTERVAL 10210 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'bearbeiten', @jetzt - INTERVAL 10210 MINUTE, @jetzt - INTERVAL 10190 MINUTE, @mehmet, @jetzt - INTERVAL 10210 MINUTE);
UPDATE arbeitsgang SET status = 'erledigt', gutmenge = 19, ausschuss = 1, fertig_gemeldet_von = @mehmet, fertig_gemeldet_am = @jetzt - INTERVAL 10190 MINUTE WHERE id = @ag;

-- FA-0999 · AG 40
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-0999' AND ag.ag_nummer = 40);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @paul, 'ruesten', @jetzt - INTERVAL 9040 MINUTE, @jetzt - INTERVAL 9030 MINUTE, @paul, @jetzt - INTERVAL 9040 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @paul, 'bearbeiten', @jetzt - INTERVAL 9030 MINUTE, @jetzt - INTERVAL 9021 MINUTE, @paul, @jetzt - INTERVAL 9030 MINUTE);
UPDATE arbeitsgang SET status = 'erledigt', gutmenge = 19, ausschuss = 0, fertig_gemeldet_von = @paul, fertig_gemeldet_am = @jetzt - INTERVAL 9021 MINUTE WHERE id = @ag;

-- FA-1001 · AG 10
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-1001' AND ag.ag_nummer = 10);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'ruesten', @jetzt - INTERVAL 7600 MINUTE, @jetzt - INTERVAL 7586 MINUTE, @mehmet, @jetzt - INTERVAL 7600 MINUTE);
INSERT INTO unterbrechung (arbeitsgang_id, benutzer_id, unterbrechungsgrund_id, beginn, ende) VALUES (@ag, @mehmet, (SELECT id FROM unterbrechungsgrund WHERE bezeichnung = 'Pause'), @jetzt - INTERVAL 7586 MINUTE, @jetzt - INTERVAL 7556 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'bearbeiten', @jetzt - INTERVAL 7556 MINUTE, @jetzt - INTERVAL 7430 MINUTE, @mehmet, @jetzt - INTERVAL 7556 MINUTE);
UPDATE arbeitsgang SET status = 'erledigt', gutmenge = 50, ausschuss = 0, fertig_gemeldet_von = @mehmet, fertig_gemeldet_am = @jetzt - INTERVAL 7430 MINUTE WHERE id = @ag;

-- FA-1001 · AG 20
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-1001' AND ag.ag_nummer = 20);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, quelle, begruendung, erfasst_von, erfasst_am) VALUES (@ag, @jonas, 'ruesten', @jetzt - INTERVAL 6180 MINUTE, @jetzt - INTERVAL 6148 MINUTE, 'nachtrag', 'Stempeln vergessen', @lena, @jetzt - INTERVAL 100 MINUTE);
INSERT INTO unterbrechung (arbeitsgang_id, benutzer_id, unterbrechungsgrund_id, beginn, ende) VALUES (@ag, @jonas, (SELECT id FROM unterbrechungsgrund WHERE bezeichnung = 'Werkzeug fehlt'), @jetzt - INTERVAL 6148 MINUTE, @jetzt - INTERVAL 6136 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @jonas, 'bearbeiten', @jetzt - INTERVAL 6136 MINUTE, @jetzt - INTERVAL 6066 MINUTE, @jonas, @jetzt - INTERVAL 6136 MINUTE);
INSERT INTO unterbrechung (arbeitsgang_id, benutzer_id, unterbrechungsgrund_id, beginn, ende) VALUES (@ag, @jonas, (SELECT id FROM unterbrechungsgrund WHERE bezeichnung = 'Pause'), @jetzt - INTERVAL 6066 MINUTE, @jetzt - INTERVAL 6036 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @jonas, 'bearbeiten', @jetzt - INTERVAL 6036 MINUTE, @jetzt - INTERVAL 5956 MINUTE, @jonas, @jetzt - INTERVAL 6036 MINUTE);
UPDATE arbeitsgang SET status = 'erledigt', gutmenge = 49, ausschuss = 1, fertig_gemeldet_von = @jonas, fertig_gemeldet_am = @jetzt - INTERVAL 5956 MINUTE WHERE id = @ag;

-- FA-1001 · AG 30
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-1001' AND ag.ag_nummer = 30);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @paul, 'ruesten', @jetzt - INTERVAL 3280 MINUTE, @jetzt - INTERVAL 3254 MINUTE, @paul, @jetzt - INTERVAL 3280 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @paul, 'bearbeiten', @jetzt - INTERVAL 3254 MINUTE, @jetzt - INTERVAL 3029 MINUTE, @paul, @jetzt - INTERVAL 3254 MINUTE);
UPDATE arbeitsgang SET status = 'erledigt', gutmenge = 48, ausschuss = 1, fertig_gemeldet_von = @paul, fertig_gemeldet_am = @jetzt - INTERVAL 3029 MINUTE WHERE id = @ag;

-- FA-1001 · AG 40
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-1001' AND ag.ag_nummer = 40);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @paul, 'ruesten', @jetzt - INTERVAL 1840 MINUTE, @jetzt - INTERVAL 1831 MINUTE, @paul, @jetzt - INTERVAL 1840 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @paul, 'bearbeiten', @jetzt - INTERVAL 1831 MINUTE, @jetzt - INTERVAL 1806 MINUTE, @paul, @jetzt - INTERVAL 1831 MINUTE);
UPDATE arbeitsgang SET status = 'erledigt', gutmenge = 48, ausschuss = 0, fertig_gemeldet_von = @paul, fertig_gemeldet_am = @jetzt - INTERVAL 1806 MINUTE WHERE id = @ag;

-- FA-1002 · AG 10
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-1002' AND ag.ag_nummer = 10);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'ruesten', @jetzt - INTERVAL 4620 MINUTE, @jetzt - INTERVAL 4611 MINUTE, @mehmet, @jetzt - INTERVAL 4620 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, quelle, gueltig, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'bearbeiten', @jetzt - INTERVAL 4611 MINUTE, @jetzt - INTERVAL 4536 MINUTE, 'terminal', 0, @mehmet, @jetzt - INTERVAL 4611 MINUTE);
SET @alt = LAST_INSERT_ID();
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, quelle, ersetzt_buchung_id, begruendung, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'bearbeiten', @jetzt - INTERVAL 4611 MINUTE, @jetzt - INTERVAL 4552 MINUTE, 'korrektur', @alt, 'Ausstempeln vergessen, Ende laut Werker nach 59 min', @lena, @jetzt - INTERVAL 4476 MINUTE);
UPDATE arbeitsgang SET status = 'erledigt', gutmenge = 30, ausschuss = 0, fertig_gemeldet_von = @mehmet, fertig_gemeldet_am = @jetzt - INTERVAL 4552 MINUTE WHERE id = @ag;

-- FA-1002 · AG 20
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-1002' AND ag.ag_nummer = 20);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @jonas, 'ruesten', @jetzt - INTERVAL 120 MINUTE, @jetzt - INTERVAL 92 MINUTE, @jonas, @jetzt - INTERVAL 120 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @jonas, 'bearbeiten', @jetzt - INTERVAL 92 MINUTE, NULL, @jonas, @jetzt - INTERVAL 92 MINUTE);
UPDATE arbeitsgang SET status = 'laeuft' WHERE id = @ag;

-- FA-1003 · AG 10
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-1003' AND ag.ag_nummer = 10);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'ruesten', @jetzt - INTERVAL 1740 MINUTE, @jetzt - INTERVAL 1726 MINUTE, @mehmet, @jetzt - INTERVAL 1740 MINUTE);
INSERT INTO unterbrechung (arbeitsgang_id, benutzer_id, unterbrechungsgrund_id, beginn, ende) VALUES (@ag, @mehmet, (SELECT id FROM unterbrechungsgrund WHERE bezeichnung = 'Material fehlt'), @jetzt - INTERVAL 1726 MINUTE, @jetzt - INTERVAL 1686 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'bearbeiten', @jetzt - INTERVAL 1686 MINUTE, @jetzt - INTERVAL 1641 MINUTE, @mehmet, @jetzt - INTERVAL 1686 MINUTE);
UPDATE arbeitsgang SET status = 'erledigt', gutmenge = 12, ausschuss = 0, fertig_gemeldet_von = @mehmet, fertig_gemeldet_am = @jetzt - INTERVAL 1641 MINUTE WHERE id = @ag;

-- FA-1000 · AG 10
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-1000' AND ag.ag_nummer = 10);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'ruesten', @jetzt - INTERVAL 3180 MINUTE, @jetzt - INTERVAL 3167 MINUTE, @mehmet, @jetzt - INTERVAL 3180 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'bearbeiten', @jetzt - INTERVAL 3167 MINUTE, @jetzt - INTERVAL 3136 MINUTE, @mehmet, @jetzt - INTERVAL 3167 MINUTE);
UPDATE arbeitsgang SET status = 'erledigt', gutmenge = 10, ausschuss = 0, fertig_gemeldet_von = @mehmet, fertig_gemeldet_am = @jetzt - INTERVAL 3136 MINUTE WHERE id = @ag;

-- FA-1004 · AG 10
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-1004' AND ag.ag_nummer = 10);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @paul, 'ruesten', @jetzt - INTERVAL 42 MINUTE, @jetzt - INTERVAL 32 MINUTE, @paul, @jetzt - INTERVAL 42 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @paul, 'bearbeiten', @jetzt - INTERVAL 32 MINUTE, NULL, @paul, @jetzt - INTERVAL 32 MINUTE);
UPDATE arbeitsgang SET status = 'laeuft' WHERE id = @ag;

-- FA-1000 · AG 20
SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = 'FA-1000' AND ag.ag_nummer = 20);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'ruesten', @jetzt - INTERVAL 85 MINUTE, @jetzt - INTERVAL 37 MINUTE, @mehmet, @jetzt - INTERVAL 85 MINUTE);
INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @mehmet, 'bearbeiten', @jetzt - INTERVAL 37 MINUTE, @jetzt - INTERVAL 22 MINUTE, @mehmet, @jetzt - INTERVAL 37 MINUTE);
INSERT INTO unterbrechung (arbeitsgang_id, benutzer_id, unterbrechungsgrund_id, beginn, ende) VALUES (@ag, @mehmet, (SELECT id FROM unterbrechungsgrund WHERE bezeichnung = 'Maschinenstörung'), @jetzt - INTERVAL 22 MINUTE, NULL);
UPDATE arbeitsgang SET status = 'unterbrochen' WHERE id = @ag;

-- Prüfergebnisse: Bei erledigten Arbeitsgängen sind alle Prüfpunkte in Ordnung
UPDATE pruefpunkt pp
JOIN arbeitsgang ag ON ag.id = pp.arbeitsgang_id
SET pp.istwert     = CASE WHEN pp.art = 'messwert' THEN ROUND((pp.untere_grenze + pp.obere_grenze) / 2, 3) END,
    pp.ergebnis    = 'in_ordnung',
    pp.geprueft_von = ag.fertig_gemeldet_von,
    pp.geprueft_am  = ag.fertig_gemeldet_am
WHERE ag.status = 'erledigt';

-- Beispiel für eine Nacharbeit: erst außerhalb der Toleranz, nach Nacharbeit in Ordnung
UPDATE pruefpunkt pp
JOIN arbeitsgang ag       ON ag.id = pp.arbeitsgang_id
JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id
SET pp.istwert = 160.030,
    pp.bemerkung = 'Erstmessung 160,070 außerhalb der Toleranz, nachgearbeitet'
WHERE fa.auftragsnummer = 'FA-0999' AND ag.ag_nummer = 30 AND pp.reihenfolge = 1;

-- FA-1002 · AG 20 läuft noch: der erste Prüfpunkt ist schon gemessen
UPDATE pruefpunkt pp
JOIN arbeitsgang ag       ON ag.id = pp.arbeitsgang_id
JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id
SET pp.istwert = 200.040, pp.ergebnis = 'in_ordnung', pp.geprueft_von = @jonas, pp.geprueft_am = @jetzt - INTERVAL 30 MINUTE
WHERE fa.auftragsnummer = 'FA-1002' AND ag.ag_nummer = 20 AND pp.reihenfolge = 1;

-- Historie: Die Rüstzeit für das Lackieren der V-410 wurde schon einmal aus Ist-Werten angepasst
INSERT INTO arbeitsplan_aenderung (arbeitsplan_position_id, feld, alter_wert, neuer_wert, begruendung, geaendert_von, geaendert_am)
SELECT pos.id, 'ruestzeit', 20.00, 25.00, 'Ø Ist-Rüstzeit aus 3 Aufträgen lag bei 25 min', @anna, @jetzt - INTERVAL 30 DAY
FROM arbeitsplan_position pos JOIN artikel a ON a.id = pos.artikel_id
WHERE a.artikelnummer = 'V-410' AND pos.ag_nummer = 30;

