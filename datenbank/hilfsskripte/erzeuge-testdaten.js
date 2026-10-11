// Erzeugt ../02_testdaten.sql mit stimmigen Beispieldaten passend zum Prototyp.
// Aufruf: node erzeuge-testdaten.js
//
// Alle Zeitpunkte sind relativ zum Importzeitpunkt (@jetzt). Dadurch zeigt die
// Hallenansicht nach jedem Import laufende Arbeitsgänge mit passenden Zeiten.
// Die Abläufe je Arbeitsgang werden als Folge von Abschnitten beschrieben:
//   ["R", 42]                 Rüsten 42 min
//   ["B", 110]                Bearbeiten 110 min
//   ["U", "Pause", 30]        Unterbrechung mit Grund, 30 min
//   ["B", 29, { korrigiert: 45, begruendung: "…" }]  zuerst falsch gebucht, dann korrigiert
//   ["R", 42, { nachtrag: "lena", begruendung: "…", erfasstVorMin: 100 }]
// Der letzte Abschnitt eines laufenden Arbeitsgangs hat kein Ende.

const fs = require("fs");
const path = require("path");

const zeilen = [];
const sql = (s) => zeilen.push(s);
const zeit = (minVorher) => (minVorher === 0 ? "@jetzt" : `@jetzt - INTERVAL ${minVorher} MINUTE`);
const tag = (tage) => (tage === 0 ? "@heute" : tage > 0 ? `@heute + INTERVAL ${tage} DAY` : `@heute - INTERVAL ${-tage} DAY`);
const text = (t) => (t === null || t === undefined ? "NULL" : `'${String(t).replace(/'/g, "''")}'`);
const TAG = 1440;

sql(`-- =====================================================================
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
`);

// ---------------------------------------------------------------------
// Stammdaten
// ---------------------------------------------------------------------
const benutzer = [
  ["1000", "Sven", "Petersen", "admin", 1, "sven"],
  ["1001", "Anna", "Becker", "arbeitsvorbereitung", 1, "anna"],
  ["1002", "Lena", "Hoffmann", "meister", 1, "lena"],
  ["1010", "Tobias", "Krüger", "controlling", 1, "tobias"],
  ["1042", "Jonas", "Wolff", "werker", 1, "jonas"],
  ["1043", "Mehmet", "Yilmaz", "werker", 1, "mehmet"],
  ["1045", "Paul", "Schneider", "werker", 1, "paul"],
  ["1044", "Katrin", "Wagner", "werker", 0, "katrin"],
];
const mail = (v, n) => `${v}.${n}`.toLowerCase().replace(/ü/g, "ue").replace(/ä/g, "ae").replace(/ö/g, "oe") + "@fa-digital.de";
sql("-- Benutzer");
sql("INSERT INTO benutzer (personalnummer, vorname, nachname, email, passwort_hash, rolle, aktiv) VALUES");
sql(benutzer.map(([nr, v, n, rolle, aktiv]) => `  (${text(nr)}, ${text(v)}, ${text(n)}, ${text(mail(v, n))}, @hash, ${text(rolle)}, ${aktiv})`).join(",\n") + ";");
for (const [nr, , , , , kurz] of benutzer) sql(`SET @${kurz} = (SELECT id FROM benutzer WHERE personalnummer = '${nr}');`);
sql("");

sql("-- Arbeitsplätze");
sql(`INSERT INTO arbeitsplatz (nummer, bezeichnung, kostenstelle, stundensatz, aktiv) VALUES
  ('AP-10', 'Säge', '4100', 45.00, 1),
  ('AP-20', 'CNC-Drehmaschine', '4200', 85.00, 1),
  ('AP-30', 'CNC-Fräsmaschine', '4300', 95.00, 1),
  ('AP-40', 'Messplatz', '4400', 50.00, 1),
  ('AP-50', 'Montage', '4500', 55.00, 1),
  ('AP-60', 'Konventionelle Drehmaschine', '4210', 60.00, 0);
`);

sql("-- Artikel");
sql(`INSERT INTO artikel (artikelnummer, bezeichnung) VALUES
  ('W-200', 'Antriebswelle Ø40'),
  ('F-310', 'Flansch DN80'),
  ('G-120', 'Lagergehäuse'),
  ('B-050', 'Distanzbuchse');
`);

const arbeitsplan = [
  ["W-200", 10, "Sägen", "AP-10", 10, 1.5],
  ["W-200", 20, "Drehen", "AP-20", 30, 4.0],
  ["W-200", 30, "Prüfen", "AP-40", 0, 1.0],
  ["F-310", 10, "Sägen", "AP-10", 10, 1.0],
  ["F-310", 20, "Drehen", "AP-20", 25, 3.0],
  ["F-310", 30, "Fräsen", "AP-30", 40, 5.0],
  ["F-310", 40, "Prüfen", "AP-40", 0, 1.5],
  ["G-120", 10, "Fräsen", "AP-30", 45, 8.0],
  ["G-120", 20, "Montage", "AP-50", 15, 6.0],
  ["G-120", 30, "Prüfen", "AP-40", 0, 2.0],
  ["B-050", 10, "Sägen", "AP-10", 5, 0.5],
  ["B-050", 20, "Drehen", "AP-20", 20, 1.5],
];
sql("-- Arbeitspläne: Soll-Rüstzeit je Auftrag und Soll-Stückzeit in Minuten");
sql(`INSERT INTO arbeitsplan_position (artikel_id, ag_nummer, bezeichnung, arbeitsplatz_id, ruestzeit_min, stueckzeit_min)
SELECT a.id, v.ag, v.bez, p.id, v.ruest, v.stueck
FROM (
${arbeitsplan.map(([art, ag, bez, ap, r, s], i) => `  ${i === 0 ? "SELECT" : "UNION ALL SELECT"} ${text(art)} AS art, ${ag} AS ag, ${text(bez)} AS bez, ${text(ap)} AS ap, ${r} AS ruest, ${s} AS stueck`).join("\n")}
) v
JOIN artikel a      ON a.artikelnummer = v.art
JOIN arbeitsplatz p ON p.nummer = v.ap;
`);

sql("-- Zertifikatsarten nach DIN EN 10204");
sql(`INSERT INTO zertifikatsart (kuerzel, bezeichnung, beschreibung) VALUES
  ('2.1', 'Werksbescheinigung 2.1', 'Bestätigung, dass die Lieferung der Bestellung entspricht, ohne Prüfergebnisse'),
  ('2.2', 'Werkszeugnis 2.2', 'Bestätigung mit Ergebnissen nichtspezifischer Prüfungen'),
  ('3.1', 'Abnahmeprüfzeugnis 3.1', 'Ergebnisse spezifischer Prüfungen, bestätigt durch einen von der Fertigung unabhängigen Abnahmebeauftragten');
`);

sql("-- Unterbrechungsgründe am Werker-Terminal");
sql(`INSERT INTO unterbrechungsgrund (bezeichnung, geplant) VALUES
  ('Pause', 1),
  ('Material fehlt', 0),
  ('Maschinenstörung', 0),
  ('Werkzeug fehlt', 0),
  ('Sonstiges', 0);
`);

// Prüfmerkmale: [Artikel, AG, Reihenfolge, Bezeichnung, Art, Soll, untere, obere, Einheit, Zertifikat]
const merkmale = [
  ["W-200", 20, 1, "Durchmesser Lagersitz Ø40 h6", "messwert", 40.0, 39.984, 40.0, "mm", null],
  ["W-200", 20, 2, "Gesamtlänge 250 ±0,2", "messwert", 250.0, 249.8, 250.2, "mm", null],
  ["W-200", 30, 1, "Sichtprüfung: Kanten entgratet, keine Riefen", "sichtpruefung", null, null, null, null, null],
  ["W-200", 30, 2, "Rundlauf Lagersitz", "messwert", 0.0, 0.0, 0.02, "mm", "2.2"],
  ["W-200", 30, 3, "Rauheit Lagersitz Ra", "messwert", 0.4, 0.0, 0.8, "µm", "2.2"],
  ["F-310", 20, 1, "Außendurchmesser Ø200 ±0,1", "messwert", 200.0, 199.9, 200.1, "mm", null],
  ["F-310", 30, 1, "Lochkreis Ø160 ±0,05", "messwert", 160.0, 159.95, 160.05, "mm", null],
  ["F-310", 40, 1, "Sichtprüfung Dichtfläche", "sichtpruefung", null, null, null, null, null],
  ["F-310", 40, 2, "Planlauf Dichtfläche", "messwert", 0.0, 0.0, 0.05, "mm", "2.2"],
  ["G-120", 10, 1, "Bohrung Ø62 H7", "messwert", 62.0, 62.0, 62.03, "mm", null],
  ["G-120", 30, 1, "Sichtprüfung: Montage vollständig", "sichtpruefung", null, null, null, null, null],
  ["B-050", 20, 1, "Länge 30 ±0,1", "messwert", 30.0, 29.9, 30.1, "mm", null],
];
const zahl = (z) => (z === null ? "NULL" : String(z));
sql("-- Prüfmerkmale: Vorlage der Checklisten. Mit Zertifikatsart = nur, wenn der Kunde dieses Zertifikat fordert");
sql(`INSERT INTO pruefmerkmal (arbeitsplan_position_id, reihenfolge, bezeichnung, art, sollwert, untere_grenze, obere_grenze, einheit, zertifikatsart_id)
SELECT pos.id, v.nr, v.bez, v.art, v.soll, v.unten, v.oben, v.einheit, za.id
FROM (
${merkmale.map(([art, ag, nr, bez, typ, s, u, o, e, z], i) => `  ${i === 0 ? "SELECT" : "UNION ALL SELECT"} ${text(art)} AS artikel, ${ag} AS ag, ${nr} AS nr, ${text(bez)} AS bez, ${text(typ)} AS art, ${zahl(s)} AS soll, ${zahl(u)} AS unten, ${zahl(o)} AS oben, ${text(e)} AS einheit, ${text(z)} AS zert`).join("\n")}
) v
JOIN artikel a                ON a.artikelnummer = v.artikel
JOIN arbeitsplan_position pos ON pos.artikel_id = a.id AND pos.ag_nummer = v.ag
LEFT JOIN zertifikatsart za   ON za.kuerzel = v.zert;
`);

// ---------------------------------------------------------------------
// Fertigungsaufträge
// [Nummer, Artikel, Menge, Kunde, Liefertermin (Tage ab heute), Status,
//  angelegt (Min. vorher), freigegeben, abgeschlossen, nachkalkuliert, Zertifikate]
// ---------------------------------------------------------------------
const auftraege = [
  ["FA-0997", "F-310", 16, "Hansa Pumpen AG", 9, "storniert", 9 * TAG + 300, null, null, null, []],
  ["FA-0998", "W-200", 80, "Nordwerk Maschinenbau GmbH", -8, "nachkalkuliert", 14 * TAG + 500, 14 * TAG + 450, 10 * TAG + 200, 9 * TAG + 300, [["2.2", "freigegeben", 10 * TAG + 150]]],
  ["FA-0999", "F-310", 20, "Hansa Pumpen AG", -2, "nachkalkuliert", 10 * TAG + 500, 10 * TAG + 450, 5 * TAG + 200, 4 * TAG + 300, [["2.1", "freigegeben", 5 * TAG + 150]]],
  ["FA-1000", "G-120", 10, "Baltic Lager GmbH", -3, "in_arbeit", 8 * TAG + 500, 7 * TAG + 450, null, null, []],
  ["FA-1001", "W-200", 50, "Nordwerk Maschinenbau GmbH", 4, "fertig", 6 * TAG + 500, 6 * TAG + 450, 30, null, [["2.2", "gefordert", null]]],
  ["FA-1002", "F-310", 30, "Hansa Pumpen AG", 8, "in_arbeit", 4 * TAG + 500, 4 * TAG + 450, null, null, [["2.2", "gefordert", null]]],
  ["FA-1003", "B-050", 200, "Kieler Werft Service", 11, "in_arbeit", 3 * TAG + 500, 3 * TAG + 450, null, null, []],
  ["FA-1004", "W-200", 40, "Nordwerk Maschinenbau GmbH", 16, "in_arbeit", 2 * TAG + 500, 50, null, null, [["3.1", "gefordert", null]]],
  ["FA-1005", "G-120", 8, "Baltic Lager GmbH", 18, "freigegeben", 1 * TAG + 300, 120, null, null, []],
  ["FA-1006", "B-050", 120, "Kieler Werft Service", 23, "angelegt", 10, null, null, null, [["2.2", "gefordert", null]]],
];
const oder = (m) => (m === null ? "NULL" : zeit(m));
sql("-- Fertigungsaufträge");
sql("INSERT INTO fertigungsauftrag (auftragsnummer, artikel_id, menge, kunde, liefertermin, status, angelegt_von, angelegt_am, freigegeben_von, freigegeben_am, abgeschlossen_von, abgeschlossen_am, nachkalkuliert_am) VALUES");
sql(auftraege.map(([nr, art, menge, kunde, lt, status, an, frei, ab, nk]) =>
  `  (${text(nr)}, (SELECT id FROM artikel WHERE artikelnummer = ${text(art)}), ${menge}, ${text(kunde)}, ${tag(lt)}, ${text(status)}, @anna, ${zeit(an)}, ${frei === null ? "NULL" : "@anna"}, ${oder(frei)}, ${ab === null ? "NULL" : "@lena"}, ${oder(ab)}, ${oder(nk)})`).join(",\n") + ";");
sql("");

sql("-- Geforderte Werkszertifikate (werden bei der Auftragsanlage festgelegt)");
const zert = [];
for (const [nr, , , , , , , , , , liste] of auftraege) {
  for (const [kz, status, frei] of liste) {
    zert.push(`  ((SELECT id FROM fertigungsauftrag WHERE auftragsnummer = ${text(nr)}), (SELECT id FROM zertifikatsart WHERE kuerzel = ${text(kz)}), ${text(status)}, ` +
      `${frei === null ? "NULL" : zeit(frei + 30)}, ${frei === null ? "NULL" : "@lena"}, ${oder(frei)}, ${frei === null ? "NULL" : text(`WZ_${nr}_${kz}.pdf`)})`);
  }
}
sql("INSERT INTO auftrag_zertifikat (fertigungsauftrag_id, zertifikatsart_id, status, erstellt_am, freigegeben_von, freigegeben_am, dateiname) VALUES");
sql(zert.join(",\n") + ";");
sql("");

sql(`-- Arbeitsgänge: Kopie des Arbeitsplans je Auftrag mit dem Stundensatz bei der Anlage.
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
`);

// ---------------------------------------------------------------------
// Rückmeldungen aus der Werkstatt
// [Auftrag, AG, Werker, Start (Min. vorher), Abschnitte, Abschluss]
// Abschluss: { gut, ausschuss } oder "laeuft" oder "unterbrochen"
// ---------------------------------------------------------------------
const ablaeufe = [
  ["FA-0998", 10, "mehmet", 13 * TAG + 400, [["R", 11], ["U", "Material fehlt", 55], ["B", 60], ["U", "Pause", 30], ["B", 54]], { gut: 80, ausschuss: 0 }],
  ["FA-0998", 20, "jonas", 12 * TAG + 420, [["R", 36], ["U", "Werkzeug fehlt", 30], ["B", 100], ["U", "Maschinenstörung", 25], ["B", 50], ["U", "Pause", 30], ["B", 152]], { gut: 80, ausschuss: 0 }],
  ["FA-0998", 30, "paul", 11 * TAG + 400, [["B", 40], ["U", "Sonstiges", 20], ["B", 44]], { gut: 80, ausschuss: 0 }],
  ["FA-0999", 10, "mehmet", 9 * TAG + 400, [["R", 9], ["B", 19]], { gut: 20, ausschuss: 0 }],
  ["FA-0999", 20, "jonas", 8 * TAG + 420, [["R", 27], ["U", "Werkzeug fehlt", 18], ["B", 65]], { gut: 20, ausschuss: 0 }],
  ["FA-0999", 30, "mehmet", 7 * TAG + 400, [["R", 40], ["U", "Maschinenstörung", 70], ["B", 60], ["U", "Pause", 30], ["B", 40], ["U", "Maschinenstörung", 50], ["B", 31]], { gut: 19, ausschuss: 1 }],
  ["FA-0999", 40, "paul", 6 * TAG + 400, [["B", 33]], { gut: 19, ausschuss: 0 }],
  ["FA-1001", 10, "mehmet", 5 * TAG + 400, [["R", 8], ["U", "Pause", 30], ["B", 72]], { gut: 50, ausschuss: 0 }],
  ["FA-1001", 20, "jonas", 4 * TAG + 420, [["R", 42, { nachtrag: "lena", begruendung: "Stempeln vergessen", erfasstVorMin: 100 }], ["U", "Werkzeug fehlt", 12], ["B", 110], ["U", "Pause", 30], ["B", 110]], { gut: 49, ausschuss: 1 }],
  ["FA-1001", 30, "paul", 1 * TAG + 400, [["B", 45]], { gut: 48, ausschuss: 1 }],
  ["FA-1002", 10, "mehmet", 3 * TAG + 300, [["R", 8], ["B", 29, { korrigiert: 45, begruendung: "Ausstempeln vergessen, Ende laut Werker nach 29 min" }]], { gut: 30, ausschuss: 0 }],
  ["FA-1002", 20, "jonas", 120, [["R", 28], ["B", 92]], "laeuft"],
  ["FA-1003", 10, "mehmet", 1 * TAG + 300, [["R", 5], ["U", "Material fehlt", 40], ["B", 60], ["U", "Pause", 30], ["B", 33]], { gut: 200, ausschuss: 0 }],
  ["FA-1004", 10, "paul", 42, [["R", 10], ["B", 32]], "laeuft"],
  ["FA-1000", 10, "mehmet", 85, [["R", 48], ["B", 15], ["U", "Maschinenstörung", 22]], "unterbrochen"],
];

sql("-- Zeitbuchungen und Unterbrechungen je Arbeitsgang");
for (const [nr, ag, werker, start, abschnitte, abschluss] of ablaeufe) {
  sql(`\n-- ${nr} · AG ${ag}`);
  sql(`SET @ag = (SELECT ag.id FROM arbeitsgang ag JOIN fertigungsauftrag fa ON fa.id = ag.fertigungsauftrag_id WHERE fa.auftragsnummer = ${text(nr)} AND ag.ag_nummer = ${ag});`);
  let t = start;
  abschnitte.forEach((a, i) => {
    const letzter = i === abschnitte.length - 1;
    const offen = letzter && typeof abschluss === "string";
    if (a[0] === "U") {
      const [, grund, dauer] = a;
      const ende = offen ? "NULL" : zeit(t - dauer);
      sql(`INSERT INTO unterbrechung (arbeitsgang_id, benutzer_id, unterbrechungsgrund_id, beginn, ende) VALUES (@ag, @${werker}, (SELECT id FROM unterbrechungsgrund WHERE bezeichnung = ${text(grund)}), ${zeit(t)}, ${ende});`);
      t -= dauer;
      return;
    }
    const [art, dauer, extra = {}] = a;
    const taetigkeit = art === "R" ? "ruesten" : "bearbeiten";
    const ende = offen ? "NULL" : zeit(t - dauer);
    if (extra.korrigiert) {
      // falsche Buchung vom Terminal, danach Korrektur durch die Meisterin
      sql(`INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, quelle, gueltig, erfasst_von, erfasst_am) VALUES (@ag, @${werker}, '${taetigkeit}', ${zeit(t)}, ${zeit(t - extra.korrigiert)}, 'terminal', 0, @${werker}, ${zeit(t)});`);
      sql(`SET @alt = LAST_INSERT_ID();`);
      sql(`INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, quelle, ersetzt_buchung_id, begruendung, erfasst_von, erfasst_am) VALUES (@ag, @${werker}, '${taetigkeit}', ${zeit(t)}, ${ende}, 'korrektur', @alt, ${text(extra.begruendung)}, @lena, ${zeit(t - extra.korrigiert - 60)});`);
    } else if (extra.nachtrag) {
      sql(`INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, quelle, begruendung, erfasst_von, erfasst_am) VALUES (@ag, @${werker}, '${taetigkeit}', ${zeit(t)}, ${ende}, 'nachtrag', ${text(extra.begruendung)}, @${extra.nachtrag}, ${zeit(extra.erfasstVorMin)});`);
    } else {
      sql(`INSERT INTO zeitbuchung (arbeitsgang_id, benutzer_id, taetigkeit, beginn, ende, erfasst_von, erfasst_am) VALUES (@ag, @${werker}, '${taetigkeit}', ${zeit(t)}, ${ende}, @${werker}, ${zeit(t)});`);
    }
    t -= dauer;
  });
  if (typeof abschluss === "string") {
    sql(`UPDATE arbeitsgang SET status = '${abschluss}' WHERE id = @ag;`);
  } else {
    sql(`UPDATE arbeitsgang SET status = 'erledigt', gutmenge = ${abschluss.gut}, ausschuss = ${abschluss.ausschuss}, fertig_gemeldet_von = @${werker}, fertig_gemeldet_am = ${zeit(t)} WHERE id = @ag;`);
  }
}

sql(`
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

-- Historie: Die Rüstzeit für das Drehen der B-050 wurde schon einmal aus Ist-Werten angepasst
INSERT INTO arbeitsplan_aenderung (arbeitsplan_position_id, feld, alter_wert, neuer_wert, begruendung, geaendert_von, geaendert_am)
SELECT pos.id, 'ruestzeit', 15.00, 20.00, 'Ø Ist-Rüstzeit aus 3 Aufträgen lag bei 20 min', @anna, @jetzt - INTERVAL 30 DAY
FROM arbeitsplan_position pos JOIN artikel a ON a.id = pos.artikel_id
WHERE a.artikelnummer = 'B-050' AND pos.ag_nummer = 20;
`);

fs.writeFileSync(path.join(__dirname, "..", "02_testdaten.sql"), zeilen.join("\n") + "\n");
console.log("02_testdaten.sql geschrieben,", zeilen.length, "Zeilen");
