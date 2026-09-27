// Erzeugt die Prozessdiagramme für das Fachkonzept als HTML-Seiten (SVG) im Stil der Vorlage
// „Fachkonzept Meldung Arbeitsort“: Überschrift als Aussagesatz, Bahnen je Rolle,
// Kreise für Start und Ende, Rechtecke für Tätigkeiten, Rauten für Entscheidungen.
// Aufruf: node prozesse.js  →  schreibt die HTML-Seiten in diesen Ordner.
// Die PNG-Bilder entstehen danach per Screenshot im Browser.

const fs = require("fs");
const path = require("path");

const BLAU = "#00305d";
const RAND = "#000e23";
const SCHRIFT = "Verdana, sans-serif";
const AUFGABE_B = 250, AUFGABE_H = 150, RADIUS = 90, RAUTE_B = 250, RAUTE_H = 160;

function textZeilen(cx, cy, zeilen, groesse = 20) {
  const abstand = groesse * 1.25;
  const start = -((zeilen.length - 1) / 2) * abstand;
  return `<text x="${cx}" y="${cy}" font-size="${groesse}" text-anchor="middle" dominant-baseline="middle" fill="${BLAU}">` +
    zeilen.map((z, i) => `<tspan x="${cx}" dy="${i === 0 ? start : abstand}">${z}</tspan>`).join("") + `</text>`;
}

const kreis = (cx, cy, zeilen) =>
  `<circle cx="${cx}" cy="${cy}" r="${RADIUS}" fill="#fff" stroke="${RAND}" stroke-width="2"/>` + textZeilen(cx, cy, zeilen);

const aufgabe = (x, y, zeilen) =>
  `<rect x="${x}" y="${y}" width="${AUFGABE_B}" height="${AUFGABE_H}" fill="#fff" stroke="${RAND}" stroke-width="2"/>` +
  textZeilen(x + AUFGABE_B / 2, y + AUFGABE_H / 2, zeilen);

const raute = (cx, cy, zeilen) => {
  const b = RAUTE_B / 2, h = RAUTE_H / 2;
  return `<polygon points="${cx - b},${cy} ${cx},${cy - h} ${cx + b},${cy} ${cx},${cy + h}" fill="#fff" stroke="${RAND}" stroke-width="2"/>` +
    textZeilen(cx, cy, zeilen);
};

// Pfeil entlang eines Pfads aus Punkten, Spitze am letzten Punkt
const pfeil = (punkte) =>
  `<polyline points="${punkte.map((p) => p.join(",")).join(" ")}" fill="none" stroke="${BLAU}" stroke-width="1.6" marker-end="url(#spitze)"/>`;

const beschriftung = (x, y, text, anker = "start") =>
  `<text x="${x}" y="${y}" font-size="20" text-anchor="${anker}" fill="${BLAU}">${text}</text>`;

const bahn = (y, name) => `<text x="46" y="${y}" font-size="36" fill="${BLAU}">${name}</text>`;

const trenner = (y, breite) =>
  `<line x1="30" y1="${y}" x2="${breite - 30}" y2="${y}" stroke="${BLAU}" stroke-width="2" stroke-dasharray="2,5"/>`;

function seite(breite, hoehe, titel, inhalt) {
  const kopf = titel.map((zeile, i) =>
    `<text x="40" y="${95 + i * 72}" font-size="60" fill="${BLAU}">${zeile}</text>`).join("");
  return `<!DOCTYPE html>
<html lang="de"><head><meta charset="UTF-8"><title>${titel.join(" ")}</title>
<style>html,body{margin:0;background:#fff}svg{display:block;font-family:${SCHRIFT}}</style></head>
<body><svg xmlns="http://www.w3.org/2000/svg" width="${breite}" height="${hoehe}" viewBox="0 0 ${breite} ${hoehe}">
<defs><marker id="spitze" viewBox="0 0 10 10" refX="10" refY="5" markerUnits="userSpaceOnUse" markerWidth="13" markerHeight="13" orient="auto"><path d="M0,0 L10,5 L0,10 z" fill="${BLAU}"/></marker></defs>
<rect width="${breite}" height="${hoehe}" fill="#fff"/>
${kopf}
<rect x="40" y="${titel.length > 1 ? 205 : 135}" width="160" height="6" fill="${BLAU}"/>
${inhalt.join("\n")}
</svg></body></html>`;
}

const diagramme = {
  // Zurückgestellt (27.09.2026): Stammdatenpflege, bis klar ist, wie sie mit den Auftragsdaten zusammenspielt
  "zurueckgestellt/stammdaten.html": seite(1840, 580,
    ["Artikel, Arbeitspläne und Arbeitsplätze werden", "von der Arbeitsvorbereitung gepflegt."], [
      bahn(300, "Arbeitsvorbereitung"),
      kreis(160, 450, ["Neuer", "Artikel wird", "gefertigt"]),
      aufgabe(310, 375, ["Artikel mit", "Arbeitsplan", "erfassen"]),
      kreis(900, 450, ["Neue", "Maschine wird", "aufgestellt"]),
      aufgabe(1050, 375, ["Arbeitsplatz mit", "Stundensatz", "erfassen"]),
      kreis(1700, 450, ["Ende"]),
      pfeil([[250, 450], [310, 450]]),
      pfeil([[560, 450], [620, 450], [620, 335], [1700, 335], [1700, 360]]),
      pfeil([[990, 450], [1050, 450]]),
      pfeil([[1300, 450], [1610, 450]]),
    ]),

  // Fertigungsauftrag abwickeln
  "auftrag.html": seite(2070, 1620,
    ["Ein Fertigungsauftrag wird angelegt, in der", "Werkstatt gebucht und nachkalkuliert."], [
      bahn(290, "Arbeitsvorbereitung"),
      kreis(160, 420, ["Kundenauftrag", "liegt vor"]),
      aufgabe(310, 345, ["Fertigungsauftrag", "anlegen"]),
      aufgabe(620, 345, ["Auftrag", "freigeben"]),
      pfeil([[250, 420], [310, 420]]),
      pfeil([[560, 420], [620, 420]]),
      trenner(540, 2070),

      bahn(595, "Werker/innen"),
      aufgabe(620, 655, ["Arbeitsgang", "rüsten und", "bearbeiten"]),
      raute(1045, 730, ["Unterbrechung", "nötig?"]),
      aufgabe(920, 870, ["Mit Grund", "unterbrechen"]),
      aufgabe(1230, 655, ["Arbeitsgang", "fertig melden", "(Gut/Ausschuss)"]),
      raute(1665, 730, ["Weitere", "Arbeitsgänge?"]),
      pfeil([[745, 495], [745, 655]]),
      pfeil([[870, 730], [920, 730]]),
      pfeil([[1045, 810], [1045, 870]]), beschriftung(1058, 850, "Ja"),
      pfeil([[920, 945], [745, 945], [745, 805]]), beschriftung(832, 932, "fortsetzen", "middle"),
      pfeil([[1170, 730], [1230, 730]]), beschriftung(1200, 715, "Nein", "middle"),
      pfeil([[1480, 730], [1540, 730]]),
      pfeil([[1665, 650], [1665, 625], [800, 625], [800, 655]]), beschriftung(1678, 645, "Ja"),
      pfeil([[1665, 810], [1665, 1145]]), beschriftung(1678, 850, "Nein"),
      trenner(1060, 2070),

      bahn(1115, "Meister/in"),
      aufgabe(1540, 1145, ["Buchungen prüfen,", "Auftrag", "abschließen"]),
      pfeil([[1665, 1295], [1665, 1420]]),
      trenner(1335, 2070),

      bahn(1390, "Controlling"),
      aufgabe(1540, 1420, ["Nachkalkulation", "erstellen"]),
      kreis(1940, 1495, ["Ende"]),
      pfeil([[1790, 1495], [1850, 1495]]),
    ]),

  // Checklisten im Arbeitsgang abarbeiten und Daten für das Werkszertifikat liefern
  "checkliste.html": seite(2070, 1080,
    ["Werker arbeiten im Arbeitsgang Checklisten ab und", "liefern die Daten für das Werkszertifikat."], [
      bahn(290, "Werker/innen"),
      kreis(160, 420, ["Arbeitsgang", "läuft"]),
      aufgabe(310, 345, ["Checkliste des", "Arbeitsgangs", "öffnen"]),
      aufgabe(620, 345, ["Prüfpunkte", "abhaken und", "Messwerte", "erfassen"]),
      raute(1045, 420, ["Alles in der", "Toleranz?"]),
      aufgabe(920, 560, ["Abweichung", "erfassen und", "nacharbeiten"]),
      aufgabe(1230, 345, ["Checkliste", "abschließen"]),
      pfeil([[250, 420], [310, 420]]),
      pfeil([[560, 420], [620, 420]]),
      pfeil([[870, 420], [920, 420]]),
      pfeil([[1045, 500], [1045, 560]]), beschriftung(1058, 540, "Nein"),
      pfeil([[920, 635], [745, 635], [745, 495]]), beschriftung(832, 622, "erneut prüfen", "middle"),
      pfeil([[1170, 420], [1230, 420]]), beschriftung(1200, 405, "Ja", "middle"),
      trenner(760, 2070),

      bahn(815, "Meister/in"),
      aufgabe(1230, 850, ["Prüfdaten aller", "Arbeitsgänge", "kontrollieren"]),
      aufgabe(1540, 850, ["Werkszertifikat", "erstellen und", "freigeben"]),
      kreis(1940, 925, ["Ende"]),
      pfeil([[1355, 495], [1355, 850]]),
      pfeil([[1480, 925], [1540, 925]]),
      pfeil([[1790, 925], [1850, 925]]),
    ]),

  // Aus Ist-Zeiten lernen
  "verbesserung.html": seite(1840, 920,
    ["Aus Nachkalkulation und Auswertung werden", "Arbeitspläne und Abläufe verbessert."], [
      bahn(300, "Arbeitsvorbereitung"),
      kreis(160, 430, ["Auftrag", "ist nach-", "kalkuliert"]),
      aufgabe(310, 355, ["Soll-Zeit-", "Vorschläge", "prüfen"]),
      raute(745, 430, ["Abweichung", "über 10 %?"]),
      aufgabe(990, 355, ["Soll-Zeit in", "Arbeitsplan", "übernehmen"]),
      kreis(1700, 430, ["Ende"]),
      pfeil([[250, 430], [310, 430]]),
      pfeil([[560, 430], [620, 430]]),
      pfeil([[870, 430], [990, 430]]), beschriftung(930, 415, "Ja", "middle"),
      pfeil([[1240, 430], [1610, 430]]),
      pfeil([[745, 510], [745, 545], [1700, 545], [1700, 520]]), beschriftung(760, 537, "Nein"),
      trenner(585, 1840),

      bahn(640, "Controlling"),
      kreis(160, 765, ["Monat", "endet"]),
      aufgabe(310, 690, ["Auswertung", "erstellen"]),
      aufgabe(620, 690, ["Unterbrechungen", "nach Grund", "prüfen"]),
      raute(1055, 765, ["Häufige", "Störungen?"]),
      aufgabe(1300, 690, ["Maßnahmen mit", "Instandhaltung", "abstimmen"]),
      kreis(1700, 765, ["Ende"]),
      pfeil([[250, 765], [310, 765]]),
      pfeil([[560, 765], [620, 765]]),
      pfeil([[870, 765], [930, 765]]),
      pfeil([[1180, 765], [1300, 765]]), beschriftung(1240, 750, "Ja", "middle"),
      pfeil([[1550, 765], [1610, 765]]),
      pfeil([[1055, 845], [1055, 880], [1700, 880], [1700, 855]]), beschriftung(1070, 872, "Nein"),
    ]),
};

for (const [datei, html] of Object.entries(diagramme)) {
  fs.writeFileSync(path.join(__dirname, datei), html);
  console.log("geschrieben:", datei);
}
