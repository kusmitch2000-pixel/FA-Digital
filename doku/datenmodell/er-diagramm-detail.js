// Erzeugt die ausführliche Übersicht des Datenmodells für die Querformat-Seite im Dokument
// „Datenmodell FA-Digital“: alle 14 Tabellen mit allen Spalten und alle 27 Fremdschlüssel,
// auch die zehn Verweise auf benutzer (orange). Stil wie er-diagramm.png.
// Aufruf: node er-diagramm-detail.js  →  schreibt er-diagramm-detail.html in diesen Ordner.
// Das PNG entsteht danach per Screenshot im Browser. Eine Einheit entspricht 0,1 mm auf der Seite.

const fs = require("fs");
const path = require("path");

const BLAU = "#00305d";
const RAND = "#000e23";
const ORANGE = "#cf6a00";
const SCHRIFT = "Verdana, sans-serif";
const BREITE = 2670, HOEHE = 1540;
const KOPF = 38, ZEILE = 29, UNTEN = 6;

const BEREICH = {
  stamm: { name: "Stammdaten", kopf: "#e8eef7", text: BLAU },
  auftrag: { name: "Auftragsdaten", kopf: "#b9cbe3", text: BLAU },
  rueck: { name: "Rückmeldungen aus der Werkstatt", kopf: BLAU, text: "#fff" },
  historie: { name: "Historie", kopf: "#d9dde3", text: BLAU },
};

// Spalten wie in datenbank/01_schema.sql. PK = Primärschlüssel, FK = Fremdschlüssel,
// FKB = Fremdschlüssel auf benutzer
const tabellen = {
  arbeitsplatz: { bereich: "stamm", x: 110, y: 64, b: 205, spalten: [
    ["id", "PK"], ["nummer"], ["bezeichnung"], ["kostenstelle"], ["stundensatz"], ["aktiv"]] },
  arbeitsplan_position: { bereich: "stamm", x: 110, y: 359, b: 305, spalten: [
    ["id", "PK"], ["artikel_id", "FK"], ["ag_nummer"], ["bezeichnung"], ["arbeitsplatz_id", "FK"],
    ["ruestzeit_min"], ["stueckzeit_min"]] },
  artikel: { bereich: "stamm", x: 110, y: 683, b: 228, spalten: [
    ["id", "PK"], ["artikelnummer"], ["bezeichnung"], ["aktiv"]] },
  pruefmerkmal: { bereich: "stamm", x: 110, y: 920, b: 322, spalten: [
    ["id", "PK"], ["arbeitsplan_position_id", "FK"], ["reihenfolge"], ["bezeichnung"], ["art"],
    ["sollwert"], ["untere_grenze"], ["obere_grenze"], ["einheit"], ["zertifikatsart_id", "FK"]] },
  zertifikatsart: { bereich: "stamm", x: 110, y: 1331, b: 215, spalten: [
    ["id", "PK"], ["kuerzel"], ["bezeichnung"], ["beschreibung"]] },

  fertigungsauftrag: { bereich: "auftrag", x: 640, y: 64, b: 282, spalten: [
    ["id", "PK"], ["auftragsnummer"], ["artikel_id", "FK"], ["menge"], ["kunde"], ["liefertermin"],
    ["status"], ["bemerkung"], ["angelegt_von", "FKB"], ["angelegt_am"], ["freigegeben_von", "FKB"],
    ["freigegeben_am"], ["abgeschlossen_von", "FKB"], ["abgeschlossen_am"], ["nachkalkuliert_am"]] },
  pruefpunkt: { bereich: "auftrag", x: 640, y: 659, b: 255, spalten: [
    ["id", "PK"], ["arbeitsgang_id", "FK"], ["pruefmerkmal_id", "FK"], ["reihenfolge"], ["bezeichnung"],
    ["art"], ["sollwert"], ["untere_grenze"], ["obere_grenze"], ["einheit"], ["istwert"], ["ergebnis"],
    ["bemerkung"], ["geprueft_von", "FKB"], ["geprueft_am"]] },
  auftrag_zertifikat: { bereich: "auftrag", x: 640, y: 1254, b: 292, spalten: [
    ["id", "PK"], ["fertigungsauftrag_id", "FK"], ["zertifikatsart_id", "FK"], ["status"], ["erstellt_am"],
    ["freigegeben_von", "FKB"], ["freigegeben_am"], ["dateiname"]] },

  arbeitsgang: { bereich: "auftrag", x: 1250, y: 64, b: 322, spalten: [
    ["id", "PK"], ["fertigungsauftrag_id", "FK"], ["arbeitsplan_position_id", "FK"], ["ag_nummer"],
    ["bezeichnung"], ["arbeitsplatz_id", "FK"], ["soll_ruestzeit_min"], ["soll_stueckzeit_min"],
    ["stundensatz"], ["status"], ["gutmenge"], ["ausschuss"], ["fertig_gemeldet_von", "FKB"],
    ["fertig_gemeldet_am"], ["bemerkung"]] },
  benutzer: { bereich: "stamm", x: 1284, y: 640, b: 255, spalten: [
    ["id", "PK"], ["personalnummer"], ["vorname"], ["nachname"], ["email"], ["passwort_hash"], ["rolle"],
    ["aktiv"], ["angelegt_am"]] },

  arbeitsplan_aenderung: { bereich: "historie", x: 1900, y: 64, b: 342, spalten: [
    ["id", "PK"], ["arbeitsplan_position_id", "FK"], ["feld"], ["alter_wert"], ["neuer_wert"],
    ["begruendung"], ["geaendert_von", "FKB"], ["geaendert_am"]] },
  zeitbuchung: { bereich: "rueck", x: 1900, y: 400, b: 285, spalten: [
    ["id", "PK"], ["arbeitsgang_id", "FK"], ["benutzer_id", "FKB"], ["taetigkeit"], ["beginn"], ["ende"],
    ["quelle"], ["gueltig"], ["ersetzt_buchung_id", "FK"], ["begruendung"], ["erfasst_von", "FKB"],
    ["erfasst_am"]] },
  unterbrechung: { bereich: "rueck", x: 1900, y: 900, b: 338, spalten: [
    ["id", "PK"], ["arbeitsgang_id", "FK"], ["benutzer_id", "FKB"], ["unterbrechungsgrund_id", "FK"],
    ["beginn"], ["ende"], ["bemerkung"]] },

  unterbrechungsgrund: { bereich: "stamm", x: 2320, y: 980, b: 322, spalten: [
    ["id", "PK"], ["bezeichnung"], ["geplant"], ["aktiv"]] },
};

const hoehe = (t) => KOPF + ZEILE * t.spalten.length + UNTEN;
const zeileY = (t, spalte) => t.y + KOPF + ZEILE * (t.spalten.findIndex(([s]) => s === spalte) + 0.5);

// Fremdschlüssel: von [Tabelle, Spalte, Seite L/R] nach [Tabelle, Seite L/R/O/U, Lage].
// weg: Zwischenwerte abwechselnd x, y, x … Die Linie startet waagerecht und biegt an jedem Wert ab.
// ziel: Anzahl auf der Seite des Primärschlüssels (1 oder 0..1, wenn der Fremdschlüssel leer sein darf).
// Reihenfolge der Kanäle so gewählt, dass sich möglichst wenige Linien kreuzen.
const verbindungen = [
  // Stammdaten untereinander
  { von: ["arbeitsplan_position", "artikel_id", "L"], weg: [65], nach: ["artikel", "L", 720], ziel: "1" },
  { von: ["arbeitsplan_position", "arbeitsplatz_id", "R"], weg: [440], nach: ["arbeitsplatz", "R", 250], ziel: "1" },
  { von: ["pruefmerkmal", "arbeitsplan_position_id", "L"], weg: [25], nach: ["arbeitsplan_position", "L", 395], ziel: "1" },
  { von: ["pruefmerkmal", "zertifikatsart_id", "L"], weg: [45], nach: ["zertifikatsart", "L", 1385], ziel: "0..1" },
  // Auftragsdaten
  { von: ["fertigungsauftrag", "artikel_id", "L"], weg: [540], nach: ["artikel", "R", 745], ziel: "1" },
  { von: ["auftrag_zertifikat", "fertigungsauftrag_id", "L"], weg: [600], nach: ["fertigungsauftrag", "L", 510], ziel: "1" },
  { von: ["auftrag_zertifikat", "zertifikatsart_id", "L"], weg: [], nach: ["zertifikatsart", "R"], ziel: "1" },
  { von: ["pruefpunkt", "pruefmerkmal_id", "L"], weg: [560], nach: ["pruefmerkmal", "R", 960], ziel: "0..1" },
  { von: ["arbeitsgang", "fertigungsauftrag_id", "L"], weg: [], nach: ["fertigungsauftrag", "R"], ziel: "1" },
  { von: ["arbeitsgang", "arbeitsplan_position_id", "R"], weg: [1600, 46, 500], nach: ["arbeitsplan_position", "R", 585], ziel: "0..1" },
  { von: ["arbeitsgang", "arbeitsplatz_id", "R"], weg: [1625, 30, 460], nach: ["arbeitsplatz", "R", 150], ziel: "1" },
  { von: ["pruefpunkt", "arbeitsgang_id", "R"], weg: [1000], nach: ["arbeitsgang", "L", 520], ziel: "1" },
  // Rückmeldungen aus der Werkstatt
  { von: ["zeitbuchung", "arbeitsgang_id", "L"], weg: [], nach: ["arbeitsgang", "R"], ziel: "1" },
  { von: ["zeitbuchung", "ersetzt_buchung_id", "R"], weg: [2230], nach: ["zeitbuchung", "R", 452.5], ziel: "0..1" },
  { von: ["unterbrechung", "arbeitsgang_id", "L"], weg: [1750], nach: ["arbeitsgang", "R", 525], ziel: "1" },
  { von: ["unterbrechung", "unterbrechungsgrund_id", "R"], weg: [], nach: ["unterbrechungsgrund", "L"], ziel: "1" },
  // Historie
  { von: ["arbeitsplan_aenderung", "arbeitsplan_position_id", "L"], weg: [1780, 14, 480], nach: ["arbeitsplan_position", "R", 555], ziel: "1" },

  // Verweise auf benutzer
  { von: ["fertigungsauftrag", "angelegt_von", "R"], weg: [1110], nach: ["benutzer", "L", 700], ziel: "1" },
  { von: ["fertigungsauftrag", "freigegeben_von", "R"], weg: [1075], nach: ["benutzer", "L", 735], ziel: "0..1" },
  { von: ["fertigungsauftrag", "abgeschlossen_von", "R"], weg: [1040], nach: ["benutzer", "L", 770], ziel: "0..1" },
  { von: ["arbeitsgang", "fertig_gemeldet_von", "L"], weg: [1150], nach: ["benutzer", "L", 665], ziel: "0..1" },
  { von: ["pruefpunkt", "geprueft_von", "R"], weg: [1185], nach: ["benutzer", "L", 860], ziel: "0..1" },
  { von: ["auftrag_zertifikat", "freigegeben_von", "R"], weg: [1215], nach: ["benutzer", "L", 905], ziel: "0..1" },
  { von: ["arbeitsplan_aenderung", "geaendert_von", "L"], weg: [1650], nach: ["benutzer", "R", 670], ziel: "1" },
  { von: ["zeitbuchung", "benutzer_id", "L"], weg: [1680], nach: ["benutzer", "R", 710], ziel: "1" },
  { von: ["zeitbuchung", "erfasst_von", "L"], weg: [], nach: ["benutzer", "R"], ziel: "1" },
  { von: ["unterbrechung", "benutzer_id", "L"], weg: [1710], nach: ["benutzer", "R", 900], ziel: "1" },
];

// Eckpunkte einer Verbindung: waagerecht aus der Spalte heraus, dann rechtwinklig über weg zum Ziel
function eckpunkte(v) {
  const [tName, spalte, seite] = v.von;
  const t = tabellen[tName];
  let x = seite === "L" ? t.x : t.x + t.b;
  let y = zeileY(t, spalte);
  const p = [[x, y]];
  v.weg.forEach((wert, i) => {
    if (i % 2 === 0) x = wert; else y = wert;
    p.push([x, y]);
  });
  const [zName, zSeite, lage] = v.nach;
  const z = tabellen[zName];
  const naechsteSenkrecht = v.weg.length % 2 === 1;
  if (zSeite === "L" || zSeite === "R") {
    const zx = zSeite === "L" ? z.x : z.x + z.b;
    if (naechsteSenkrecht) p.push([x, lage]);
    p.push([zx, naechsteSenkrecht ? lage : y]);
  } else {
    const zy = zSeite === "O" ? z.y : z.y + hoehe(z);
    if (!naechsteSenkrecht) p.push([lage, y]);
    p.push([naechsteSenkrecht ? x : lage, zy]);
  }
  return p;
}

// Anzahl neben dem Linienende, außerhalb der Tabelle
function anzahl([x, y], [vx, vy], text, farbe) {
  const nachRechts = x > vx, nachLinks = x < vx, nachUnten = y > vy;
  let tx, ty, anker = "start";
  if (nachLinks) { tx = x + 7; ty = y - 7; }
  else if (nachRechts) { tx = x - 7; ty = y - 7; anker = "end"; }
  else if (nachUnten) { tx = x + 7; ty = y - 9; }
  else { tx = x + 7; ty = y + 21; }
  return `<text x="${tx}" y="${ty}" font-size="20" font-weight="bold" text-anchor="${anker}" fill="${farbe}">${text}</text>`;
}

function linie(v) {
  const p = eckpunkte(v);
  const farbe = v.von && tabellen[v.von[0]].spalten.find(([s]) => s === v.von[1])[1] === "FKB" ? ORANGE : BLAU;
  const punkte = p.map(([x, y]) => `${x},${y}`).join(" ");
  // Weißer Rand unter jeder Linie: Wo sich Linien kreuzen, liegt die spätere sichtbar darüber.
  const strich = `<polyline points="${punkte}" fill="none" stroke="#fff" stroke-width="10"/>` +
    `<polyline points="${punkte}" fill="none" stroke="${farbe}" stroke-width="2.4"/>`;
  const start = anzahl(p[0], p[1], "n", farbe);
  const ende = anzahl(p[p.length - 1], p[p.length - 2], v.ziel, farbe);
  return { farbe, strich, beschriftung: start + ende };
}

function tabelle(name, t) {
  const h = hoehe(t);
  const bereich = BEREICH[t.bereich];
  let s = `<rect x="${t.x}" y="${t.y}" width="${t.b}" height="${h}" fill="#fff" stroke="${RAND}" stroke-width="2"/>` +
    `<rect x="${t.x}" y="${t.y}" width="${t.b}" height="${KOPF}" fill="${bereich.kopf}" stroke="${RAND}" stroke-width="2"/>` +
    `<text x="${t.x + 12}" y="${t.y + KOPF / 2}" font-size="24" font-weight="bold" dominant-baseline="central" fill="${bereich.text}">${name}</text>`;
  t.spalten.forEach(([spalte, schluessel], i) => {
    const y = t.y + KOPF + ZEILE * (i + 0.5);
    if (schluessel) {
      const farbe = schluessel === "FKB" ? ORANGE : BLAU;
      s += `<text x="${t.x + 12}" y="${y}" font-size="16" font-weight="bold" dominant-baseline="central" fill="${farbe}">${schluessel === "PK" ? "PK" : "FK"}</text>`;
    }
    const unterstrichen = schluessel === "PK" ? ` text-decoration="underline"` : "";
    s += `<text x="${t.x + 46}" y="${y}" font-size="22" dominant-baseline="central" fill="${BLAU}"${unterstrichen}>${spalte}</text>`;
  });
  return s;
}

function legende(x, y) {
  let s = `<text x="${x}" y="${y}" font-size="24" font-weight="bold" fill="${BLAU}">Legende</text>`;
  Object.values(BEREICH).forEach((b, i) => {
    const yz = y + 38 + i * 36;
    s += `<rect x="${x}" y="${yz - 12}" width="34" height="24" fill="${b.kopf}" stroke="${RAND}" stroke-width="2"/>` +
      `<text x="${x + 48}" y="${yz}" font-size="21" dominant-baseline="central" fill="${BLAU}">${b.name}</text>`;
  });
  const x2 = x + 450;
  [[BLAU, "Beziehung zwischen Tabellen"], [ORANGE, "Verweis auf benutzer"]].forEach(([farbe, text], i) => {
    const yz = y + 38 + i * 36;
    s += `<line x1="${x2}" y1="${yz}" x2="${x2 + 44}" y2="${yz}" stroke="${farbe}" stroke-width="2.4"/>` +
      `<text x="${x2 + 58}" y="${yz}" font-size="21" dominant-baseline="central" fill="${BLAU}">${text}</text>`;
  });
  s += `<text x="${x}" y="${y + 196}" font-size="21" fill="${BLAU}">PK = Primärschlüssel, FK = Fremdschlüssel</text>` +
    `<text x="${x}" y="${y + 230}" font-size="21" fill="${BLAU}">1, n, 0..1 = Anzahl der Datensätze je Beziehung</text>`;
  return s;
}

const linien = verbindungen.map(linie);
// Orange zuerst, damit die blauen Beziehungen an Kreuzungen oben liegen
const inhalt =
  linien.filter((l) => l.farbe === ORANGE).map((l) => l.strich).join("") +
  linien.filter((l) => l.farbe === BLAU).map((l) => l.strich).join("") +
  Object.entries(tabellen).map(([name, t]) => tabelle(name, t)).join("") +
  linien.map((l) => l.beschriftung).join("") +
  legende(1300, 1250);

const html = `<!DOCTYPE html>
<html lang="de"><head><meta charset="UTF-8"><title>Datenmodell FA-Digital – Gesamtübersicht</title>
<style>html,body{margin:0;background:#fff}svg{display:block;font-family:${SCHRIFT}}</style></head>
<body><svg xmlns="http://www.w3.org/2000/svg" width="${BREITE}" height="${HOEHE}" viewBox="0 0 ${BREITE} ${HOEHE}">
${inhalt}
</svg></body></html>
`;

fs.writeFileSync(path.join(__dirname, "er-diagramm-detail.html"), html);
console.log("er-diagramm-detail.html geschrieben");
