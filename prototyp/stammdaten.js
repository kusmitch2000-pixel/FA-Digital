// Nur für den Prototyp: Stammdaten zum Ausprobieren auf der Website.
// Arbeitsplätze, Artikel mit Arbeitsplan, Zertifikate und neu angelegte Aufträge. Änderungen
// merkt sich der Browser (localStorage), jeder Besucher hat also seinen eigenen Stand.
// Die Beispieldaten sind dieselben wie in datenbank/02_testdaten.sql (Laravel-App).
//
// Seiten:
// - artikel.html:          Artikel anlegen und jedem Artikel seine Arbeitsschritte zuordnen
// - zertifikate.html:      eigene Zertifikate für bestimmte Artikel anlegen (Arbeitsvorbereitung)
// - auftrag-anlegen.html:  Artikel, Menge, Liefertermin und Zertifikate wählen, Vorschau rechnet live
// - auftraege.html:        neue Aufträge oben, Suche und Status-Filter, aktueller Prozess je Auftrag
// - arbeitsplaetze.html:   Arbeitsplätze anlegen und bearbeiten (Station, Stundensatz, aktiv)

// Stationen in der Reihenfolge des Fertigungsflusses
const STATIONSFOLGE = ["Auslasern", "Umformen", "Bearbeitung", "Zerspanung", "Lackierung", "Versand"];

// Arbeitsplätze der Beispieldaten. ablauf = Kurzname in der Zerspanung (Drehen oder Fräsen)
const ARBEITSPLAETZE_BEISPIEL = [
  { nummer: "AP-10", bezeichnung: "Laserschneidanlage", station: "Auslasern", kostenstelle: "4100", stundensatz: 110, aktiv: true },
  { nummer: "AP-20", bezeichnung: "Abkantpresse", station: "Umformen", kostenstelle: "4200", stundensatz: 75, aktiv: true },
  { nummer: "AP-30", bezeichnung: "Schweißplatz", station: "Bearbeitung", kostenstelle: "4300", stundensatz: 65, aktiv: true },
  { nummer: "AP-40", bezeichnung: "CNC-Drehmaschine", station: "Zerspanung", ablauf: "Drehen", kostenstelle: "4400", stundensatz: 85, aktiv: true },
  { nummer: "AP-50", bezeichnung: "CNC-Fräsmaschine", station: "Zerspanung", ablauf: "Fräsen", kostenstelle: "4500", stundensatz: 95, aktiv: true },
  { nummer: "AP-60", bezeichnung: "Lackieranlage", station: "Lackierung", kostenstelle: "4600", stundensatz: 70, aktiv: true },
  { nummer: "AP-70", bezeichnung: "Versandplatz", station: "Versand", kostenstelle: "4700", stundensatz: 40, aktiv: true },
  { nummer: "AP-90", bezeichnung: "Konventionelle Drehmaschine", station: "Zerspanung", ablauf: "Drehen", kostenstelle: "4410", stundensatz: 60, aktiv: false },
];

// Vorschlag für einen neuen Arbeitsschritt je Station
const STATIONEN = [
  { station: "Auslasern", arbeitsgang: "Auslasern", arbeitsplatz: "AP-10" },
  { station: "Umformen", arbeitsgang: "Kanten", arbeitsplatz: "AP-20" },
  { station: "Bearbeitung", arbeitsgang: "Schweißen", arbeitsplatz: "AP-30" },
  { station: "Zerspanung", arbeitsgang: "Fräsen", arbeitsplatz: "AP-50" },
  { station: "Lackierung", arbeitsgang: "Lackieren", arbeitsplatz: "AP-60" },
  { station: "Versand", arbeitsgang: "Verpacken und versenden", arbeitsplatz: "AP-70" },
];

const ARTIKEL_BEISPIEL = [
  {
    nummer: "V-410", bezeichnung: "Seitenverkleidung Fräszentrum", art: "Verkleidungsteil",
    rohteil: "Blech DC01, 2 mm", aktiv: true,
    plan: [
      { arbeitsgang: "Auslasern", arbeitsplatz: "AP-10", ruestzeit: 15, stueckzeit: 2.5 },
      { arbeitsgang: "Kanten", arbeitsplatz: "AP-20", ruestzeit: 20, stueckzeit: 3,
        pruefmerkmale: [{ bezeichnung: "Kantwinkel 90° ±1°", art: "messwert", soll: 90, unten: 89, oben: 91, einheit: "°" }] },
      { arbeitsgang: "Lackieren", arbeitsplatz: "AP-60", ruestzeit: 25, stueckzeit: 4,
        pruefmerkmale: [{ bezeichnung: "Sichtprüfung Lackoberfläche", art: "sichtpruefung", soll: null, unten: null, oben: null, einheit: "" }] },
      { arbeitsgang: "Verpacken und versenden", arbeitsplatz: "AP-70", ruestzeit: 10, stueckzeit: 0.5 },
    ],
  },
  {
    nummer: "V-420", bezeichnung: "Späneschutzwanne", art: "Verkleidungsteil",
    rohteil: "Blech 1.4301, 3 mm", aktiv: true,
    plan: [
      { arbeitsgang: "Auslasern", arbeitsplatz: "AP-10", ruestzeit: 15, stueckzeit: 3 },
      { arbeitsgang: "Kanten zur Wanne", arbeitsplatz: "AP-20", ruestzeit: 25, stueckzeit: 6,
        pruefmerkmale: [{ bezeichnung: "Innenmaß Wanne 600 ±1", art: "messwert", soll: 600, unten: 599, oben: 601, einheit: "mm" }] },
      { arbeitsgang: "Ecken verschweißen", arbeitsplatz: "AP-30", ruestzeit: 20, stueckzeit: 12,
        pruefmerkmale: [{ bezeichnung: "Sichtprüfung Schweißnähte", art: "sichtpruefung", soll: null, unten: null, oben: null, einheit: "" }] },
      { arbeitsgang: "Lackieren", arbeitsplatz: "AP-60", ruestzeit: 25, stueckzeit: 5 },
      { arbeitsgang: "Verpacken und versenden", arbeitsplatz: "AP-70", ruestzeit: 10, stueckzeit: 1 },
    ],
  },
  {
    nummer: "V-430", bezeichnung: "Schutztür mit Sichtfenster", art: "Verkleidungsteil",
    rohteil: "Blech DC01, 2 mm", aktiv: true,
    plan: [
      { arbeitsgang: "Auslasern", arbeitsplatz: "AP-10", ruestzeit: 15, stueckzeit: 4 },
      { arbeitsgang: "Kanten", arbeitsplatz: "AP-20", ruestzeit: 20, stueckzeit: 5,
        pruefmerkmale: [{ bezeichnung: "Kantwinkel 90° ±1°", art: "messwert", soll: 90, unten: 89, oben: 91, einheit: "°" }] },
      { arbeitsgang: "Scharniere anschweißen", arbeitsplatz: "AP-30", ruestzeit: 15, stueckzeit: 8 },
      { arbeitsgang: "Fenstersitz fräsen", arbeitsplatz: "AP-50", ruestzeit: 30, stueckzeit: 6,
        pruefmerkmale: [{ bezeichnung: "Tiefe Fenstersitz 5 ±0,1", art: "messwert", soll: 5, unten: 4.9, oben: 5.1, einheit: "mm" }] },
      { arbeitsgang: "Lackieren", arbeitsplatz: "AP-60", ruestzeit: 25, stueckzeit: 5 },
      { arbeitsgang: "Verpacken und versenden", arbeitsplatz: "AP-70", ruestzeit: 10, stueckzeit: 1 },
    ],
  },
  {
    nummer: "F-310", bezeichnung: "Flansch DN80", art: "Zerspanteil",
    rohteil: "Blech S235, 20 mm", aktiv: true,
    plan: [
      { arbeitsgang: "Ronde auslasern", arbeitsplatz: "AP-10", ruestzeit: 10, stueckzeit: 2 },
      { arbeitsgang: "Drehen", arbeitsplatz: "AP-40", ruestzeit: 25, stueckzeit: 3,
        pruefmerkmale: [{ bezeichnung: "Außendurchmesser Ø200 ±0,1", art: "messwert", soll: 200, unten: 199.9, oben: 200.1, einheit: "mm" }] },
      { arbeitsgang: "Lochkreis fräsen", arbeitsplatz: "AP-50", ruestzeit: 40, stueckzeit: 5,
        pruefmerkmale: [{ bezeichnung: "Lochkreis Ø160 ±0,05", art: "messwert", soll: 160, unten: 159.95, oben: 160.05, einheit: "mm" }] },
      { arbeitsgang: "Verpacken und versenden", arbeitsplatz: "AP-70", ruestzeit: 10, stueckzeit: 0.5 },
    ],
  },
  {
    nummer: "W-200", bezeichnung: "Antriebswelle Ø40", art: "Zerspanteil",
    rohteil: "Rundstahl Ø45, auf Länge", aktiv: true,
    plan: [
      { arbeitsgang: "Drehen", arbeitsplatz: "AP-40", ruestzeit: 30, stueckzeit: 4,
        pruefmerkmale: [{ bezeichnung: "Durchmesser Lagersitz Ø40 h6", art: "messwert", soll: 40, unten: 39.984, oben: 40, einheit: "mm" }, { bezeichnung: "Gesamtlänge 250 ±0,2", art: "messwert", soll: 250, unten: 249.8, oben: 250.2, einheit: "mm" }] },
      { arbeitsgang: "Passfedernut fräsen", arbeitsplatz: "AP-50", ruestzeit: 20, stueckzeit: 2,
        pruefmerkmale: [{ bezeichnung: "Nutbreite 12 ±0,05", art: "messwert", soll: 12, unten: 11.95, oben: 12.05, einheit: "mm" }] },
      { arbeitsgang: "Verpacken und versenden", arbeitsplatz: "AP-70", ruestzeit: 10, stueckzeit: 0.5 },
    ],
  },
];

// Prüfmerkmale je Arbeitsschritt (pruefmerkmale) prüft der Werker immer. Prüfmerkmale, die nur bei
// einem Zertifikat dazukommen, stehen beim Zertifikat.

// Zertifikate: nach DIN EN 10204 und eigene der Arbeitsvorbereitung.
// artikel leer = gilt für alle Artikel. Die Prüfmerkmale kommen bei Aufträgen mit diesem
// Zertifikat zusätzlich in die Checkliste des Arbeitsgangs (ag = AG-Nummer im Arbeitsplan).
const ZERTIFIKATE_BEISPIEL = [
  {
    kuerzel: "2.1", bezeichnung: "Werksbescheinigung 2.1", herkunft: "DIN EN 10204", artikel: [],
    beschreibung: "Bestätigung, dass die Lieferung der Bestellung entspricht, ohne Prüfergebnisse", merkmale: [],
  },
  {
    kuerzel: "2.2", bezeichnung: "Werkszeugnis 2.2", herkunft: "DIN EN 10204", artikel: ["F-310", "W-200"],
    beschreibung: "Bestätigung mit Ergebnissen nichtspezifischer Prüfungen",
    merkmale: [
      { artikel: "F-310", ag: 20, bezeichnung: "Planlauf Dichtfläche", art: "messwert", soll: 0, unten: 0, oben: 0.05, einheit: "mm" },
      { artikel: "W-200", ag: 10, bezeichnung: "Rundlauf Lagersitz", art: "messwert", soll: 0, unten: 0, oben: 0.02, einheit: "mm" },
      { artikel: "W-200", ag: 10, bezeichnung: "Rauheit Lagersitz Ra", art: "messwert", soll: 0.4, unten: 0, oben: 0.8, einheit: "µm" },
    ],
  },
  {
    kuerzel: "3.1", bezeichnung: "Abnahmeprüfzeugnis 3.1", herkunft: "DIN EN 10204", artikel: [],
    beschreibung: "Ergebnisse spezifischer Prüfungen, bestätigt durch einen von der Fertigung unabhängigen Abnahmebeauftragten", merkmale: [],
  },
  {
    kuerzel: "LP", bezeichnung: "Lackprüfprotokoll", herkunft: "eigenes", artikel: ["V-410", "V-420", "V-430"],
    beschreibung: "Eigenes Protokoll für lackierte Verkleidungsteile: Schichtdicke und Oberfläche",
    merkmale: [
      { artikel: "V-410", ag: 30, bezeichnung: "Schichtdicke Lack", art: "messwert", soll: 80, unten: 60, oben: 100, einheit: "µm" },
      { artikel: "V-420", ag: 40, bezeichnung: "Schichtdicke Lack", art: "messwert", soll: 80, unten: 60, oben: 100, einheit: "µm" },
      { artikel: "V-430", ag: 50, bezeichnung: "Schichtdicke Lack", art: "messwert", soll: 80, unten: 60, oben: 100, einheit: "µm" },
    ],
  },
  {
    kuerzel: "DP", bezeichnung: "Dichtheitsprotokoll", herkunft: "eigenes", artikel: ["V-420"],
    beschreibung: "Eigenes Protokoll für Späneschutzwannen: Wasserprobe der Schweißnähte",
    merkmale: [
      { artikel: "V-420", ag: 30, bezeichnung: "Dichtheit: Wasserprobe 30 min", art: "sichtpruefung", soll: null, unten: null, oben: null, einheit: "" },
    ],
  },
];

// Die Aufträge der Beispieldaten (Tabelle auf auftraege.html): Artikel und geforderte Zertifikate.
// loeschbar = noch kein Arbeitsgang gestartet (angelegt, freigegeben oder storniert).
const BEISPIEL_AUFTRAEGE = [
  { nummer: "FA-0997", artikel: "F-310", zertifikate: [], loeschbar: true },
  { nummer: "FA-0998", artikel: "V-410", zertifikate: ["LP"] },
  { nummer: "FA-0999", artikel: "F-310", zertifikate: ["2.1"] },
  { nummer: "FA-1000", artikel: "V-420", zertifikate: ["DP"] },
  { nummer: "FA-1001", artikel: "V-410", zertifikate: ["LP"] },
  { nummer: "FA-1002", artikel: "F-310", zertifikate: ["2.2"] },
  { nummer: "FA-1003", artikel: "V-430", zertifikate: [] },
  { nummer: "FA-1004", artikel: "F-310", zertifikate: ["3.1"] },
  { nummer: "FA-1005", artikel: "V-420", zertifikate: [], loeschbar: true },
  { nummer: "FA-1006", artikel: "W-200", zertifikate: ["2.2"], loeschbar: true },
];

// Version im Namen: ältere Stände mit anderen Arbeitsplatznummern werden nicht mehr gelesen
const SPEICHER_ARTIKEL = "fa-digital-artikel-v3";
const SPEICHER_ZERTIFIKATE = "fa-digital-zertifikate-v1";
const SPEICHER_AUFTRAEGE = "fa-digital-auftraege-v2";
const SPEICHER_GELOESCHT = "fa-digital-auftraege-geloescht-v1";
const SPEICHER_NUMMER = "fa-digital-auftragsnummer-v1";
const SPEICHER_ARBEITSPLAETZE = "fa-digital-arbeitsplaetze-v1";

// Arbeitsplätze: Beispieldaten oder der im Browser gespeicherte Stand (arbeitsplaetze.html)
const ARBEITSPLAETZE = lesen(SPEICHER_ARBEITSPLAETZE, null) || JSON.parse(JSON.stringify(ARBEITSPLAETZE_BEISPIEL));

// ---------- Speicher im Browser ----------

function lesen(schluessel, ersatz) {
  try {
    const wert = JSON.parse(localStorage.getItem(schluessel));
    return wert || ersatz;
  } catch (fehler) {
    return ersatz;
  }
}

function schreiben(schluessel, wert) {
  try {
    if (wert === null) {
      localStorage.removeItem(schluessel);
    } else {
      localStorage.setItem(schluessel, JSON.stringify(wert));
    }
  } catch (fehler) {
    // ohne Speicher gilt die Änderung nur bis zum Neuladen
  }
}

function alleArtikel() {
  return lesen(SPEICHER_ARTIKEL, null) || JSON.parse(JSON.stringify(ARTIKEL_BEISPIEL));
}

function arbeitsplatz(nummer) {
  return ARBEITSPLAETZE.find(function (platz) { return platz.nummer === nummer; });
}

// Rang eines Arbeitsplatzes im Fertigungsfluss: erst nach Station, innerhalb der Station nach der Liste
// (in der Zerspanung also erst Drehen, dann Fräsen)
function rangImAblauf(nummer) {
  const index = ARBEITSPLAETZE.findIndex(function (platz) { return platz.nummer === nummer; });
  const station = index < 0 ? -1 : STATIONSFOLGE.indexOf(ARBEITSPLAETZE[index].station);
  return (station < 0 ? STATIONSFOLGE.length : station) * 1000 + index;
}

// Die Reihenfolge im Arbeitsplan folgt dem Fertigungsfluss:
// Auslasern → Umformen → Bearbeitung → Zerspanung (erst Drehen, dann Fräsen) → Lackierung → Versand.
// Zwei Schritte am selben Arbeitsplatz behalten die Reihenfolge, in der sie hinzugefügt wurden.
function nachAblaufSortiert(plan) {
  const rang = function (schritt) {
    return rangImAblauf(schritt.arbeitsplatz);
  };
  return plan.map(function (schritt, index) { return { schritt: schritt, index: index }; })
    .sort(function (a, b) { return rang(a.schritt) - rang(b.schritt) || a.index - b.index; })
    .map(function (eintrag) { return eintrag.schritt; });
}

// Alle Aufträge, die es gerade gibt: Beispieldaten ohne die gelöschten, dazu die neu angelegten
function vorhandeneAuftraege() {
  const geloescht = lesen(SPEICHER_GELOESCHT, []);
  const beispiele = BEISPIEL_AUFTRAEGE.filter(function (auftrag) { return !geloescht.includes(auftrag.nummer); });
  const neue = lesen(SPEICHER_AUFTRAEGE, []).map(function (auftrag) {
    return { nummer: auftrag.nummer, artikel: auftrag.artikelnummer || auftrag.artikel.split(" ")[0], zertifikate: auftrag.zertifikate || [] };
  });
  return beispiele.concat(neue);
}

// Was in einem Auftrag vorkommt, lässt sich nicht löschen (in der Datenbank verhindert das der Fremdschlüssel)
function auftraegeMitArtikel(artikelnummer) {
  return vorhandeneAuftraege().filter(function (auftrag) { return auftrag.artikel === artikelnummer; })
    .map(function (auftrag) { return auftrag.nummer; });
}

function auftraegeMitZertifikat(kuerzel) {
  return vorhandeneAuftraege().filter(function (auftrag) { return auftrag.zertifikate.includes(kuerzel); })
    .map(function (auftrag) { return auftrag.nummer; });
}

// Löschen in zwei Schritten: Der erste Klick fragt nach, der zweite löscht
function loeschenBestaetigt(knopf) {
  if (knopf.dataset.sicher === "1") {
    return true;
  }
  const beschriftung = knopf.textContent;
  knopf.dataset.sicher = "1";
  knopf.textContent = "Wirklich löschen?";
  setTimeout(function () {
    if (knopf.isConnected) {
      delete knopf.dataset.sicher;
      knopf.textContent = beschriftung;
    }
  }, 4000);
  return false;
}

// Knopf zum Löschen. Mit Aufträgen ist er gesperrt und nennt den Grund
function loeschKnopf(attribut, wert, auftraege, recht) {
  const gesperrt = auftraege.length > 0;
  return '<button type="button" class="btn btn-klein btn-gefahr" ' + attribut + '="' + text(wert) + '"'
    + (recht ? ' data-recht-js="' + recht + '"' : "")
    + (gesperrt ? ' disabled title="Wird verwendet in ' + text(auftraege.join(", ")) + '"' : "") + ">Löschen</button>";
}

// ---------- Zahlen und Rechnung wie in den Sichten ----------

function zahl(wert, stellen) {
  return Number(wert).toLocaleString("de-DE", { minimumFractionDigits: stellen, maximumFractionDigits: stellen });
}

function minuten(wert) {
  return zahl(wert, Number.isInteger(wert) ? 0 : 1) + " min";
}

// Soll-Zeit = Rüstzeit + Menge × Stückzeit, Soll-Kosten = Soll-Zeit in Stunden × Stundensatz
function vorkalkulation(artikel, menge) {
  const zeilen = artikel.plan.map(function (schritt, index) {
    const platz = arbeitsplatz(schritt.arbeitsplatz);
    const sollZeit = schritt.ruestzeit + menge * schritt.stueckzeit;
    return {
      ag: (index + 1) * 10,
      arbeitsgang: schritt.arbeitsgang,
      platz: platz,
      ruestzeit: schritt.ruestzeit,
      stueckzeit: schritt.stueckzeit,
      sollZeit: sollZeit,
      sollKosten: Math.round(sollZeit / 60 * platz.stundensatz * 100) / 100,
    };
  });
  const zeit = zeilen.reduce(function (summe, zeile) { return summe + zeile.sollZeit; }, 0);
  const kosten = zeilen.reduce(function (summe, zeile) { return summe + zeile.sollKosten; }, 0);
  return { zeilen: zeilen, zeit: zeit, kosten: kosten };
}

function text(wert) {
  const element = document.createElement("span");
  element.textContent = wert;
  return element.innerHTML;
}

// Auswahlliste der Arbeitsplätze, nach Station gruppiert. Inaktive nur, wenn sie schon gewählt sind
function arbeitsplatzAuswahl(gewaehlt) {
  const gruppen = {};
  ARBEITSPLAETZE.slice().sort(function (a, b) { return rangImAblauf(a.nummer) - rangImAblauf(b.nummer); }).forEach(function (platz) {
    if (platz.aktiv !== false || platz.nummer === gewaehlt) {
      (gruppen[platz.station] = gruppen[platz.station] || []).push(platz);
    }
  });
  return Object.keys(gruppen).map(function (station) {
    return '<optgroup label="' + text(station) + '">' + gruppen[station].map(function (platz) {
      return '<option value="' + platz.nummer + '"' + (platz.nummer === gewaehlt ? " selected" : "") + ">"
        + text(platz.bezeichnung) + " (" + platz.nummer + ")</option>";
    }).join("") + "</optgroup>";
  }).join("");
}

// ---------- Zertifikate ----------

function alleZertifikate() {
  return lesen(SPEICHER_ZERTIFIKATE, null) || JSON.parse(JSON.stringify(ZERTIFIKATE_BEISPIEL));
}

// Zertifikate, die für einen Artikel bestellt werden können
function zertifikateFuer(artikelnummer) {
  return alleZertifikate().filter(function (zertifikat) {
    return zertifikat.artikel.length === 0 || zertifikat.artikel.includes(artikelnummer);
  });
}

function zusatzpruefungen(zertifikat, artikelnummer) {
  return zertifikat.merkmale.filter(function (merkmal) { return merkmal.artikel === artikelnummer; });
}

// ---------- zertifikate.html: eigene Zertifikate anlegen ----------

function zertifikateSeite() {
  const liste = document.getElementById("zertifikat-liste");
  const formular = document.getElementById("zertifikat-formular");
  const artikelFeld = document.getElementById("zertifikat-artikel");
  const merkmalZeilen = document.getElementById("merkmal-zeilen");
  const meldung = document.getElementById("zertifikat-meldung");
  const listenMeldung = document.getElementById("zertifikat-liste-meldung");
  const artikel = alleArtikel();
  let zertifikate = alleZertifikate();
  let bearbeitet = null;

  function artikelName(nummer) {
    const eintrag = artikel.find(function (a) { return a.nummer === nummer; });
    return eintrag ? nummer + " " + eintrag.bezeichnung : nummer;
  }

  function listeZeigen() {
    liste.innerHTML = zertifikate.map(function (zertifikat, index) {
      const eigen = zertifikat.herkunft === "eigenes";
      return "<tr" + (index === bearbeitet ? ' class="hervorgehoben"' : "") + ">"
        + "<td><strong>" + text(zertifikat.kuerzel) + "</strong></td>"
        + '<td style="min-width: 16rem;">' + text(zertifikat.bezeichnung) + '<br><span class="leise klein">' + text(zertifikat.beschreibung) + "</span></td>"
        + '<td><span class="badge ' + (eigen ? "freigegeben" : "offen") + '">' + (eigen ? "eigenes" : text(zertifikat.herkunft)) + "</span></td>"
        + '<td class="klein">' + (zertifikat.artikel.length === 0 ? "alle Artikel" : zertifikat.artikel.map(function (n) { return text(artikelName(n)); }).join("<br>")) + "</td>"
        + '<td class="zahl">' + zertifikat.merkmale.length + "</td>"
        + "<td>" + (eigen
          ? '<div class="knopfleiste" style="margin: 0; flex-wrap: nowrap;">'
            + '<button type="button" class="btn btn-klein btn-sekundaer" data-recht-js="zertifikat.anlegen" data-bearbeiten="' + index + '">Bearbeiten</button>'
            + loeschKnopf("data-zertifikat-loeschen", index, auftraegeMitZertifikat(zertifikat.kuerzel), "zertifikat.anlegen") + "</div>"
          : '<span class="leise klein">Norm</span>') + "</td>"
        + "</tr>";
    }).join("");
    rechteAnwenden(liste);
  }

  // Meister sehen die Zertifikate nur, das Formular hat prototyp.js für sie entfernt
  if (!formular) {
    listeZeigen();
    return;
  }

  function gewaehlteArtikel() {
    return Array.from(artikelFeld.querySelectorAll("input:checked")).map(function (feld) { return feld.value; });
  }

  function artikelZeigen(gewaehlt) {
    artikelFeld.innerHTML = artikel.map(function (eintrag) {
      return '<label class="auswahl"><input type="checkbox" value="' + text(eintrag.nummer) + '"'
        + (gewaehlt.includes(eintrag.nummer) ? " checked" : "") + "> " + text(eintrag.nummer + " " + eintrag.bezeichnung)
        + ' <span class="leise klein">(' + text(eintrag.art) + ")</span></label>";
    }).join("");
  }

  function agAuswahl(artikelnummer, gewaehlt) {
    const eintrag = artikel.find(function (a) { return a.nummer === artikelnummer; });
    if (!eintrag) {
      return "";
    }
    return eintrag.plan.map(function (schritt, index) {
      const ag = (index + 1) * 10;
      return '<option value="' + ag + '"' + (ag === gewaehlt ? " selected" : "") + ">AG " + ag + " " + text(schritt.arbeitsgang) + "</option>";
    }).join("");
  }

  function merkmaleZeigen(merkmale) {
    const auswahl = gewaehlteArtikel();
    merkmalZeilen.innerHTML = merkmale.map(function (merkmal, index) {
      const artikelOptionen = auswahl.map(function (nummer) {
        return '<option value="' + text(nummer) + '"' + (nummer === merkmal.artikel ? " selected" : "") + ">" + text(nummer) + "</option>";
      }).join("");
      const zahlFeld = function (feld, wert) {
        return '<input type="number" step="any" data-feld="' + feld + '" value="' + (wert === null || wert === undefined ? "" : wert) + '" style="width: 5.5rem;" aria-label="' + feld + '"'
          + (merkmal.art === "sichtpruefung" ? " disabled" : "") + ">";
      };
      return "<tr>"
        + '<td><select data-feld="artikel" aria-label="Artikel">' + artikelOptionen + "</select></td>"
        + '<td><select data-feld="ag" aria-label="Arbeitsgang">' + agAuswahl(merkmal.artikel, merkmal.ag) + "</select></td>"
        + '<td><input type="text" data-feld="bezeichnung" value="' + text(merkmal.bezeichnung) + '" style="min-width: 12rem;" aria-label="Prüfmerkmal" required></td>'
        + '<td><select data-feld="art" aria-label="Art"><option value="messwert"' + (merkmal.art === "messwert" ? " selected" : "") + '>Messwert</option>'
        + '<option value="sichtpruefung"' + (merkmal.art === "sichtpruefung" ? " selected" : "") + ">Sichtprüfung</option></select></td>"
        + "<td>" + zahlFeld("soll", merkmal.soll) + "</td>"
        + "<td>" + zahlFeld("unten", merkmal.unten) + "</td>"
        + "<td>" + zahlFeld("oben", merkmal.oben) + "</td>"
        + '<td><input type="text" data-feld="einheit" value="' + text(merkmal.einheit || "") + '" style="width: 4rem;" aria-label="Einheit"' + (merkmal.art === "sichtpruefung" ? " disabled" : "") + "></td>"
        + '<td><button type="button" class="btn btn-klein btn-gefahr" data-entfernen="' + index + '">Entfernen</button></td>'
        + "</tr>";
    }).join("") || '<tr><td colspan="9" class="leise">Noch keine Prüfmerkmale. Ohne Prüfmerkmale bestätigt das Zertifikat nur die Lieferung.</td></tr>';
  }

  function merkmaleLesen() {
    return Array.from(merkmalZeilen.querySelectorAll("tr")).filter(function (zeile) {
      return zeile.querySelector("input");
    }).map(function (zeile) {
      const wert = function (feld) { return zeile.querySelector('[data-feld="' + feld + '"]').value; };
      const zahlWert = function (feld) { return wert(feld) === "" ? null : Number(wert(feld)); };
      const art = wert("art");
      return {
        artikel: wert("artikel"),
        ag: Number(wert("ag")),
        bezeichnung: wert("bezeichnung").trim(),
        art: art,
        soll: art === "messwert" ? zahlWert("soll") : null,
        unten: art === "messwert" ? zahlWert("unten") : null,
        oben: art === "messwert" ? zahlWert("oben") : null,
        einheit: art === "messwert" ? wert("einheit").trim() : "",
      };
    });
  }

  function bearbeiten(index) {
    bearbeitet = index;
    const zertifikat = index === null
      ? { kuerzel: "", bezeichnung: "", beschreibung: "", herkunft: "eigenes", artikel: [], merkmale: [] }
      : zertifikate[index];
    document.getElementById("zertifikat-titel").textContent = index === null
      ? "Neues Zertifikat"
      : "Zertifikat bearbeiten: " + zertifikat.kuerzel + " " + zertifikat.bezeichnung;
    formular.kuerzel.value = zertifikat.kuerzel;
    formular.bezeichnung.value = zertifikat.bezeichnung;
    formular.beschreibung.value = zertifikat.beschreibung;
    artikelZeigen(zertifikat.artikel);
    merkmaleZeigen(zertifikat.merkmale);
    listenMeldung.hidden = true;
    meldung.hidden = true;
    listeZeigen();
  }

  liste.addEventListener("click", function (ereignis) {
    const knopf = ereignis.target.closest("[data-bearbeiten]");
    if (knopf) {
      bearbeiten(Number(knopf.dataset.bearbeiten));
      formular.scrollIntoView({ behavior: "smooth" });
    }

    const loeschen = ereignis.target.closest("[data-zertifikat-loeschen]");
    if (loeschen && loeschenBestaetigt(loeschen)) {
      const entfernt = zertifikate.splice(Number(loeschen.dataset.zertifikatLoeschen), 1)[0];
      schreiben(SPEICHER_ZERTIFIKATE, zertifikate);
      bearbeiten(null);
      listenMeldung.hidden = false;
      listenMeldung.textContent = "Zertifikat " + entfernt.kuerzel + " " + entfernt.bezeichnung + " wurde gelöscht.";
    }
  });

  const neu = document.getElementById("neues-zertifikat");
  if (neu) {
    neu.addEventListener("click", function () {
      bearbeiten(null);
      formular.scrollIntoView({ behavior: "smooth" });
      formular.kuerzel.focus();
    });
  }

  // Artikel abgewählt: Prüfmerkmale dieses Artikels fallen weg
  artikelFeld.addEventListener("change", function () {
    const auswahl = gewaehlteArtikel();
    merkmaleZeigen(merkmaleLesen().filter(function (merkmal) { return auswahl.includes(merkmal.artikel); }));
  });

  merkmalZeilen.addEventListener("change", function (ereignis) {
    const feld = ereignis.target.dataset.feld;
    if (feld === "artikel" || feld === "art") {
      const merkmale = merkmaleLesen();
      if (feld === "artikel") {
        const zeile = Array.from(merkmalZeilen.querySelectorAll("tr")).indexOf(ereignis.target.closest("tr"));
        merkmale[zeile].ag = 10;
      }
      merkmaleZeigen(merkmale);
    }
  });

  merkmalZeilen.addEventListener("click", function (ereignis) {
    const knopf = ereignis.target.closest("[data-entfernen]");
    if (knopf) {
      const merkmale = merkmaleLesen();
      merkmale.splice(Number(knopf.dataset.entfernen), 1);
      merkmaleZeigen(merkmale);
    }
  });

  document.getElementById("merkmal-hinzufuegen").addEventListener("click", function () {
    const auswahl = gewaehlteArtikel();
    meldung.hidden = true;
    if (auswahl.length === 0) {
      meldung.hidden = false;
      meldung.className = "hinweis fehler";
      meldung.textContent = "Bitte zuerst die Artikel auswählen, für die das Zertifikat gilt.";
      return;
    }
    const merkmale = merkmaleLesen();
    merkmale.push({ artikel: auswahl[0], ag: 10, bezeichnung: "", art: "messwert", soll: null, unten: null, oben: null, einheit: "mm" });
    merkmaleZeigen(merkmale);
  });

  formular.addEventListener("submit", function (ereignis) {
    ereignis.preventDefault();
    const kuerzel = formular.kuerzel.value.trim().toUpperCase();
    const auswahl = gewaehlteArtikel();
    const merkmale = merkmaleLesen();
    const doppelt = zertifikate.some(function (z, index) { return z.kuerzel === kuerzel && index !== bearbeitet; });
    const ohneGrenzen = merkmale.find(function (m) {
      return m.art === "messwert" && (m.unten === null || m.oben === null || m.unten > m.oben);
    });
    meldung.hidden = false;
    meldung.className = "hinweis fehler";
    if (doppelt) {
      meldung.textContent = "Das Kürzel " + kuerzel + " gibt es schon.";
      return;
    }
    if (auswahl.length === 0) {
      meldung.textContent = "Bitte mindestens einen Artikel auswählen, für den das Zertifikat gilt.";
      return;
    }
    if (ohneGrenzen) {
      meldung.textContent = "Beim Messwert „" + (ohneGrenzen.bezeichnung || "ohne Namen") + "“ fehlen die Toleranzgrenzen, oder die untere liegt über der oberen.";
      return;
    }
    const zertifikat = {
      kuerzel: kuerzel,
      bezeichnung: formular.bezeichnung.value.trim(),
      beschreibung: formular.beschreibung.value.trim(),
      herkunft: "eigenes",
      artikel: auswahl,
      merkmale: merkmale,
    };
    if (bearbeitet === null) {
      zertifikate.push(zertifikat);
      bearbeitet = zertifikate.length - 1;
    } else {
      zertifikate[bearbeitet] = zertifikat;
    }
    schreiben(SPEICHER_ZERTIFIKATE, zertifikate);
    document.getElementById("zertifikat-titel").textContent = "Zertifikat bearbeiten: " + zertifikat.kuerzel + " " + zertifikat.bezeichnung;
    meldung.className = "hinweis";
    meldung.textContent = "Gespeichert. Bei neuen Aufträgen über " + auswahl.join(", ")
      + " kann " + zertifikat.kuerzel + " jetzt gefordert werden, die Prüfmerkmale kommen dann in die Checkliste.";
    listeZeigen();
  });

  document.getElementById("zertifikate-zuruecksetzen").addEventListener("click", function () {
    schreiben(SPEICHER_ZERTIFIKATE, null);
    zertifikate = alleZertifikate();
    bearbeiten(null);
  });

  bearbeiten(null);
}

// Knöpfe, die erst nach dem Laden entstehen, nach demselben Recht wie data-recht ausblenden
function rechteAnwenden(bereich) {
  if (typeof darf !== "function" || typeof angemeldeteEmail !== "function") {
    return;
  }
  const rolle = BENUTZER[angemeldeteEmail()].rolle;
  bereich.querySelectorAll("[data-recht-js]").forEach(function (element) {
    if (!darf(rolle, element.dataset.rechtJs)) {
      element.remove();
    }
  });
}

// ---------- artikel.html: Artikel und Arbeitspläne ----------

function artikelSeite() {
  const liste = document.getElementById("artikel-liste");
  const formular = document.getElementById("artikel-formular");
  const planZeilen = document.getElementById("plan-zeilen");
  const meldung = document.getElementById("artikel-meldung");
  const listenMeldung = document.getElementById("artikel-liste-meldung");
  let artikel = alleArtikel();
  let bearbeitet = null;

  function listeZeigen() {
    liste.innerHTML = artikel.map(function (eintrag, index) {
      const ruesten = eintrag.plan.reduce(function (s, p) { return s + p.ruestzeit; }, 0);
      const stueck = eintrag.plan.reduce(function (s, p) { return s + p.stueckzeit; }, 0);
      const ablauf = eintrag.plan.map(function (p) {
        const platz = arbeitsplatz(p.arbeitsplatz);
        return text(platz.ablauf ? platz.station + " (" + platz.ablauf + ")" : platz.station);
      }).join(" → ");
      return "<tr" + (index === bearbeitet ? ' class="hervorgehoben"' : "") + ">"
        + "<td><strong>" + text(eintrag.nummer) + "</strong></td>"
        + "<td>" + text(eintrag.bezeichnung) + '<br><span class="leise klein">' + text(eintrag.art) + "</span></td>"
        + "<td>" + text(eintrag.rohteil) + "</td>"
        + '<td class="klein">' + (ablauf || '<span class="leise">noch keine Arbeitsschritte</span>') + "</td>"
        + '<td class="zahl">' + minuten(ruesten) + "</td>"
        + '<td class="zahl">' + minuten(stueck) + "</td>"
        + '<td><span class="badge ' + (eintrag.aktiv ? "aktiv" : "inaktiv") + '">' + (eintrag.aktiv ? "aktiv" : "inaktiv") + "</span></td>"
        + '<td><div class="knopfleiste" style="margin: 0; flex-wrap: nowrap;">'
        + '<button type="button" class="btn btn-klein btn-sekundaer" data-bearbeiten="' + index + '">Bearbeiten</button>'
        + loeschKnopf("data-artikel-loeschen", index, auftraegeMitArtikel(eintrag.nummer)) + "</div></td>"
        + "</tr>";
    }).join("");
  }

  // Artikel löschen. Eigene Zertifikate, die nur für diesen Artikel gelten, müssen vorher weg
  function artikelLoeschen(index) {
    const nummer = artikel[index].nummer;
    const zertifikate = alleZertifikate();
    const allein = zertifikate.find(function (z) { return z.artikel.length === 1 && z.artikel[0] === nummer; });
    listenMeldung.hidden = false;
    if (allein) {
      listenMeldung.className = "hinweis fehler";
      listenMeldung.textContent = nummer + " ist der einzige Artikel des Zertifikats " + allein.kuerzel
        + ". Bitte zuerst unter „Zertifikate“ das Zertifikat löschen oder ändern.";
      return;
    }
    zertifikate.forEach(function (z) {
      z.artikel = z.artikel.filter(function (n) { return n !== nummer; });
      z.merkmale = z.merkmale.filter(function (m) { return m.artikel !== nummer; });
    });
    schreiben(SPEICHER_ZERTIFIKATE, zertifikate);
    const entfernt = artikel.splice(index, 1)[0];
    schreiben(SPEICHER_ARTIKEL, artikel);
    bearbeiten(artikel.length > 0 ? 0 : null);
    listenMeldung.hidden = false;
    listenMeldung.className = "hinweis";
    listenMeldung.textContent = "Artikel " + entfernt.nummer + " " + entfernt.bezeichnung + " wurde gelöscht.";
  }

  // Schritt, dessen Prüfmerkmale gerade aufgeklappt sind (Index im sortierten Plan)
  let offenerSchritt = null;

  function merkmalText(merkmal) {
    return text(merkmal.bezeichnung) + (merkmal.art === "messwert"
      ? ' <span class="leise">(' + zahl(merkmal.unten, 3) + " bis " + zahl(merkmal.oben, 3) + " " + text(merkmal.einheit || "") + ")</span>"
      : ' <span class="leise">(Sichtprüfung)</span>');
  }

  // Prüfmerkmale, die nur bei einem Zertifikat dazukommen (gepflegt unter „Zertifikatsvorlagen“)
  function zertifikatsMerkmale(ag) {
    const nummer = formular.nummer.value.trim().toUpperCase();
    return alleZertifikate().flatMap(function (zertifikat) {
      return zertifikat.merkmale.filter(function (m) { return m.artikel === nummer && m.ag === ag; })
        .map(function (m) { return { merkmal: m, kuerzel: zertifikat.kuerzel }; });
    });
  }

  function merkmalEditor(schritt, index) {
    const ag = (index + 1) * 10;
    const eigene = schritt.pruefmerkmale || [];
    const zusatz = zertifikatsMerkmale(ag);
    return '<tr class="hervorgehoben" data-editor="' + index + '"><td colspan="7">'
      + '<p class="klein" style="margin: 0 0 0.5rem;"><strong>Prüfmerkmale von AG ' + ag + " " + text(schritt.arbeitsgang) + "</strong>"
      + " · prüft der Werker bei jedem Auftrag. Änderungen gelten für neue Aufträge.</p>"
      + (eigene.length ? eigene.map(function (merkmal, i) {
        return '<p class="klein" style="margin: 0 0 0.3rem;">' + merkmalText(merkmal)
          + ' <button type="button" class="btn btn-klein btn-gefahr" data-merkmal-entfernen="' + i + '">Entfernen</button></p>';
      }).join("") : '<p class="leise klein" style="margin: 0 0 0.3rem;">Noch keine Prüfmerkmale.</p>')
      + (zusatz.length ? '<p class="leise klein" style="margin: 0.5rem 0 0.3rem;">Nur bei Zertifikat (gepflegt unter <a href="zertifikate.html">Zertifikatsvorlagen</a>): '
        + zusatz.map(function (z) { return '<span class="badge freigegeben">' + text(z.kuerzel) + "</span> " + text(z.merkmal.bezeichnung); }).join(", ") + "</p>" : "")
      + '<div class="formular-raster" style="margin-top: 0.75rem;">'
      + '<div class="feld"><label for="mm-bezeichnung">Neues Prüfmerkmal</label><input type="text" id="mm-bezeichnung" placeholder="z. B. Kantwinkel 90° ±1°"></div>'
      + '<div class="feld"><label for="mm-art">Art</label><select id="mm-art"><option value="messwert">Messwert mit Toleranz</option><option value="sichtpruefung">Sichtprüfung</option></select></div>'
      + '<div class="feld" data-messwert><label for="mm-soll">Sollwert</label><input type="number" step="any" id="mm-soll"></div>'
      + '<div class="feld" data-messwert><label for="mm-unten">untere Grenze</label><input type="number" step="any" id="mm-unten"></div>'
      + '<div class="feld" data-messwert><label for="mm-oben">obere Grenze</label><input type="number" step="any" id="mm-oben"></div>'
      + '<div class="feld" data-messwert><label for="mm-einheit">Einheit</label><input type="text" id="mm-einheit" value="mm" maxlength="10"></div>'
      + "</div>"
      + '<p class="hinweis fehler" id="mm-fehler" hidden></p>'
      + '<div class="knopfleiste" style="margin: 0;">'
      + '<button type="button" class="btn btn-klein btn-primaer" data-merkmal-hinzufuegen>Prüfmerkmal hinzufügen</button>'
      + '<button type="button" class="btn btn-klein btn-sekundaer" data-merkmale-schliessen>Schließen</button>'
      + "</div></td></tr>";
  }

  function planZeigen(plan) {
    const sortiert = nachAblaufSortiert(plan);
    if (offenerSchritt !== null && offenerSchritt >= sortiert.length) {
      offenerSchritt = null;
    }
    planZeilen.innerHTML = sortiert.map(function (schritt, index) {
      const eigene = schritt.pruefmerkmale || [];
      const zusatz = zertifikatsMerkmale((index + 1) * 10);
      return '<tr data-schritt="' + index + '" data-merkmale="' + text(JSON.stringify(eigene)).replace(/"/g, "&quot;") + '">'
        + '<td class="zahl">' + (index + 1) * 10 + "</td>"
        + '<td><input type="text" data-feld="arbeitsgang" value="' + text(schritt.arbeitsgang) + '" aria-label="Arbeitsgang" required style="min-width: 14rem;"></td>'
        + '<td><select data-feld="arbeitsplatz" aria-label="Station und Arbeitsplatz" style="min-width: 15rem;">' + arbeitsplatzAuswahl(schritt.arbeitsplatz) + "</select></td>"
        + '<td><input type="number" data-feld="ruestzeit" value="' + schritt.ruestzeit + '" min="0" step="1" aria-label="Rüstzeit" style="width: 5.5rem;"></td>'
        + '<td><input type="number" data-feld="stueckzeit" value="' + schritt.stueckzeit + '" min="0" step="0.1" aria-label="Stückzeit" style="width: 5.5rem;"></td>'
        + '<td class="klein" style="min-width: 12rem;">'
        + (eigene.length || zusatz.length
          ? eigene.map(function (m) { return text(m.bezeichnung); }).concat(zusatz.map(function (z) {
            return '<span class="badge freigegeben">' + text(z.kuerzel) + "</span> " + text(z.merkmal.bezeichnung);
          })).join("<br>")
          : '<span class="leise">keine</span>')
        + '<br><button type="button" class="btn btn-klein btn-sekundaer" data-merkmale-oeffnen="' + index + '">Prüfmerkmale</button></td>'
        + '<td><button type="button" class="btn btn-klein btn-gefahr" data-entfernen="' + index + '">Entfernen</button></td>'
        + "</tr>"
        + (index === offenerSchritt ? merkmalEditor(schritt, index) : "");
    }).join("") || '<tr><td colspan="7" class="leise">Noch keine Arbeitsschritte. Unten eine Station hinzufügen.</td></tr>';
    const art = document.getElementById("mm-art");
    if (art) {
      art.addEventListener("change", function () {
        planZeilen.querySelectorAll("[data-messwert]").forEach(function (feld) { feld.hidden = art.value !== "messwert"; });
      });
    }
  }

  // Den Arbeitsplan so auslesen, wie er gerade im Formular steht
  function planLesen() {
    return Array.from(planZeilen.querySelectorAll("tr[data-schritt]")).map(function (zeile) {
      return {
        arbeitsgang: zeile.querySelector('[data-feld="arbeitsgang"]').value.trim(),
        arbeitsplatz: zeile.querySelector('[data-feld="arbeitsplatz"]').value,
        ruestzeit: Number(zeile.querySelector('[data-feld="ruestzeit"]').value) || 0,
        stueckzeit: Number(zeile.querySelector('[data-feld="stueckzeit"]').value) || 0,
        pruefmerkmale: JSON.parse(zeile.dataset.merkmale || "[]"),
      };
    });
  }

  function bearbeiten(index) {
    bearbeitet = index;
    const eintrag = index === null
      ? { nummer: "", bezeichnung: "", art: "Verkleidungsteil", rohteil: "Blech", aktiv: true, plan: [] }
      : artikel[index];
    document.getElementById("formular-titel").textContent = index === null
      ? "Neuer Artikel"
      : "Artikel bearbeiten: " + eintrag.nummer + " " + eintrag.bezeichnung;
    formular.nummer.value = eintrag.nummer;
    formular.bezeichnung.value = eintrag.bezeichnung;
    formular.art.value = eintrag.art;
    formular.rohteil.value = eintrag.rohteil;
    formular.aktiv.checked = eintrag.aktiv;
    offenerSchritt = null;
    planZeigen(eintrag.plan);
    meldung.hidden = true;
    listenMeldung.hidden = true;
    listeZeigen();
  }

  liste.addEventListener("click", function (ereignis) {
    const knopf = ereignis.target.closest("[data-bearbeiten]");
    if (knopf) {
      bearbeiten(Number(knopf.dataset.bearbeiten));
      formular.scrollIntoView({ behavior: "smooth" });
    }

    const loeschen = ereignis.target.closest("[data-artikel-loeschen]");
    if (loeschen && loeschenBestaetigt(loeschen)) {
      artikelLoeschen(Number(loeschen.dataset.artikelLoeschen));
    }
  });

  document.getElementById("neuer-artikel").addEventListener("click", function () {
    bearbeiten(null);
    formular.scrollIntoView({ behavior: "smooth" });
    formular.nummer.focus();
  });

  planZeilen.addEventListener("click", function (ereignis) {
    const knopf = ereignis.target.closest("button");
    if (!knopf) {
      return;
    }
    const plan = planLesen();
    if (knopf.dataset.entfernen !== undefined) {
      plan.splice(Number(knopf.dataset.entfernen), 1);
      offenerSchritt = null;
    } else if (knopf.dataset.merkmaleOeffnen !== undefined) {
      const index = Number(knopf.dataset.merkmaleOeffnen);
      offenerSchritt = offenerSchritt === index ? null : index;
    } else if (knopf.dataset.merkmaleSchliessen !== undefined) {
      offenerSchritt = null;
    } else if (knopf.dataset.merkmalEntfernen !== undefined) {
      plan[offenerSchritt].pruefmerkmale.splice(Number(knopf.dataset.merkmalEntfernen), 1);
    } else if (knopf.dataset.merkmalHinzufuegen !== undefined) {
      const wert = function (id) { return document.getElementById(id).value.trim(); };
      const zahlWert = function (id) { return wert(id) === "" ? null : Number(wert(id)); };
      const fehler = document.getElementById("mm-fehler");
      const merkmal = {
        bezeichnung: wert("mm-bezeichnung"),
        art: wert("mm-art"),
        soll: null, unten: null, oben: null, einheit: "",
      };
      if (merkmal.art === "messwert") {
        merkmal.soll = zahlWert("mm-soll");
        merkmal.unten = zahlWert("mm-unten");
        merkmal.oben = zahlWert("mm-oben");
        merkmal.einheit = wert("mm-einheit");
      }
      const meldungText = !merkmal.bezeichnung ? "Bitte eine Bezeichnung eintragen."
        : merkmal.art === "messwert" && (merkmal.unten === null || merkmal.oben === null) ? "Bei einem Messwert bitte untere und obere Grenze eintragen."
          : merkmal.art === "messwert" && merkmal.unten > merkmal.oben ? "Die untere Grenze liegt über der oberen."
            : "";
      if (meldungText) {
        fehler.hidden = false;
        fehler.textContent = meldungText;
        return;
      }
      plan[offenerSchritt].pruefmerkmale.push(merkmal);
    }
    planZeigen(plan);
  });

  // Anderer Arbeitsplatz gewählt: Der Schritt rückt an seine Stelle im Ablauf
  planZeilen.addEventListener("change", function (ereignis) {
    if (ereignis.target.dataset.feld === "arbeitsplatz") {
      offenerSchritt = null;
      planZeigen(planLesen());
    }
  });

  // Ein Knopf je Station: fügt einen passenden Arbeitsschritt an der richtigen Stelle im Ablauf ein
  const stationsKnoepfe = document.getElementById("stationen");
  stationsKnoepfe.innerHTML = STATIONEN.map(function (eintrag, index) {
    return '<button type="button" class="btn btn-klein btn-sekundaer" data-station="' + index + '">+ ' + text(eintrag.station) + "</button>";
  }).join("");
  stationsKnoepfe.addEventListener("click", function (ereignis) {
    const knopf = ereignis.target.closest("[data-station]");
    if (!knopf) {
      return;
    }
    const vorlage = STATIONEN[Number(knopf.dataset.station)];
    const plan = planLesen();
    plan.push({ arbeitsgang: vorlage.arbeitsgang, arbeitsplatz: vorlage.arbeitsplatz, ruestzeit: 0, stueckzeit: 0, pruefmerkmale: [] });
    offenerSchritt = null;
    planZeigen(plan);
  });

  formular.addEventListener("submit", function (ereignis) {
    ereignis.preventDefault();
    const nummer = formular.nummer.value.trim().toUpperCase();
    const doppelt = artikel.some(function (eintrag, index) { return eintrag.nummer === nummer && index !== bearbeitet; });
    const plan = nachAblaufSortiert(planLesen());
    meldung.hidden = false;
    if (doppelt) {
      meldung.className = "hinweis fehler";
      meldung.textContent = "Die Artikelnummer " + nummer + " gibt es schon.";
      return;
    }
    if (plan.length === 0) {
      meldung.className = "hinweis fehler";
      meldung.textContent = "Bitte mindestens einen Arbeitsschritt zuordnen.";
      return;
    }
    const eintrag = {
      nummer: nummer,
      bezeichnung: formular.bezeichnung.value.trim(),
      art: formular.art.value,
      rohteil: formular.rohteil.value.trim(),
      aktiv: formular.aktiv.checked,
      plan: plan,
    };
    if (bearbeitet === null) {
      artikel.push(eintrag);
      bearbeitet = artikel.length - 1;
    } else {
      artikel[bearbeitet] = eintrag;
    }
    schreiben(SPEICHER_ARTIKEL, artikel);
    document.getElementById("formular-titel").textContent = "Artikel bearbeiten: " + eintrag.nummer + " " + eintrag.bezeichnung;
    meldung.className = "hinweis";
    meldung.textContent = "Gespeichert. Arbeitsplan und Prüfmerkmale gelten für alle neuen Aufträge über " + eintrag.nummer + ".";
    listeZeigen();
  });

  document.getElementById("beispiel-laden").addEventListener("click", function () {
    schreiben(SPEICHER_ARTIKEL, null);
    artikel = alleArtikel();
    bearbeiten(0);
  });

  bearbeiten(0);
}

// Prüfpunkte eines Auftrags je Arbeitsgang: die Prüfmerkmale des Schritts plus die der geforderten Zertifikate
function pruefpunkteFuer(artikelnummer, plan, kuerzel) {
  const zertifikate = alleZertifikate().filter(function (z) { return kuerzel.includes(z.kuerzel); });
  return plan.map(function (schritt, index) {
    const ag = (index + 1) * 10;
    return (schritt.pruefmerkmale || []).map(function (m) { return { merkmal: m, kuerzel: "" }; }).concat(zertifikate.flatMap(function (z) {
      return z.merkmale.filter(function (m) { return m.artikel === artikelnummer && m.ag === ag; })
        .map(function (m) { return { merkmal: m, kuerzel: z.kuerzel }; });
    }));
  });
}

// Arbeitsgänge eines neuen Auftrags: Kopie des Arbeitsplans mit dem Stundensatz bei der Anlage
function arbeitsplanKopie(artikel) {
  return nachAblaufSortiert(artikel.plan).map(function (schritt) {
    const platz = arbeitsplatz(schritt.arbeitsplatz) || { bezeichnung: schritt.arbeitsplatz, station: "", stundensatz: 0 };
    return {
      arbeitsgang: schritt.arbeitsgang, arbeitsplatz: schritt.arbeitsplatz, platzName: platz.bezeichnung, station: platz.station,
      stundensatz: platz.stundensatz, ruestzeit: schritt.ruestzeit, stueckzeit: schritt.stueckzeit, pruefmerkmale: schritt.pruefmerkmale || [],
    };
  });
}

function sollZeitVon(plan, menge) {
  return plan.reduce(function (summe, schritt) { return summe + schritt.ruestzeit + menge * schritt.stueckzeit; }, 0);
}

// ---------- auftrag-anlegen.html: Auftrag anlegen ----------

function auftragAnlegenSeite() {
  const formular = document.getElementById("auftrag-formular");
  const artikel = alleArtikel().filter(function (eintrag) { return eintrag.aktiv && eintrag.plan.length > 0; });
  const auswahl = formular.artikel;

  const arten = ["Verkleidungsteil", "Zerspanteil"];
  auswahl.innerHTML = arten.map(function (art) {
    const passend = artikel.filter(function (eintrag) { return eintrag.art === art; });
    return passend.length === 0 ? "" : '<optgroup label="' + art + 'e">' + passend.map(function (eintrag) {
      return '<option value="' + text(eintrag.nummer) + '">' + text(eintrag.nummer + " " + eintrag.bezeichnung) + "</option>";
    }).join("") + "</optgroup>";
  }).join("");

  // Liefertermin: in zwei Wochen
  const termin = new Date();
  termin.setDate(termin.getDate() + 14);
  formular.liefertermin.value = termin.toISOString().slice(0, 10);
  formular.liefertermin.min = new Date().toISOString().slice(0, 10);

  function vorschau() {
    const gewaehlt = artikel.find(function (eintrag) { return eintrag.nummer === auswahl.value; });
    const menge = Math.max(0, Math.floor(Number(formular.menge.value) || 0));
    if (!gewaehlt) {
      return;
    }
    const rechnung = vorkalkulation(gewaehlt, menge);
    const gewaehlteZertifikate = Array.from(document.querySelectorAll('#zertifikat-auswahl input:checked')).map(function (f) { return f.value; });
    const punkte = pruefpunkteFuer(gewaehlt.nummer, nachAblaufSortiert(gewaehlt.plan), gewaehlteZertifikate);
    document.getElementById("vorschau-titel").textContent =
      "Für " + gewaehlt.nummer + " " + gewaehlt.bezeichnung + ", " + menge + " Stück · Rohteil: " + gewaehlt.rohteil;
    document.getElementById("vorschau-zeilen").innerHTML = rechnung.zeilen.map(function (zeile, index) {
      return "<tr><td>" + zeile.ag + "</td>"
        + "<td>" + text(zeile.arbeitsgang) + "</td>"
        + "<td>" + text(zeile.platz.station) + "</td>"
        + "<td>" + text(zeile.platz.bezeichnung) + " (" + zeile.platz.nummer + ")</td>"
        + '<td class="zahl">' + minuten(zeile.ruestzeit) + "</td>"
        + '<td class="zahl">' + minuten(zeile.stueckzeit) + "</td>"
        + '<td class="zahl">' + zahl(zeile.ruestzeit, 0) + " + " + menge + " × " + zahl(zeile.stueckzeit, 1) + " = <strong>" + minuten(zeile.sollZeit) + "</strong></td>"
        + '<td class="zahl">' + zahl(zeile.platz.stundensatz, 2) + " €/h</td>"
        + '<td class="zahl">' + zahl(zeile.sollKosten, 2) + " €</td>"
        + '<td class="klein">' + ((punkte[index] || []).map(function (p) {
          return (p.kuerzel ? '<span class="badge freigegeben">' + text(p.kuerzel) + "</span> " : "") + text(p.merkmal.bezeichnung);
        }).join("<br>") || '<span class="leise">keine</span>') + "</td></tr>";
    }).join("");
    document.getElementById("vorschau-zeit").textContent = minuten(rechnung.zeit)
      + " (" + Math.floor(rechnung.zeit / 60) + ":" + String(Math.round(rechnung.zeit % 60)).padStart(2, "0") + " h)";
    document.getElementById("vorschau-kosten").textContent = zahl(rechnung.kosten, 2) + " €";
    document.getElementById("vorschau-stueck").textContent = menge > 0 ? zahl(rechnung.kosten / menge, 2) + " €" : "–";
  }

  // Zertifikate passend zum Artikel. Bereits angekreuzte bleiben angekreuzt, wenn sie auch passen
  const zertifikatFeld = document.getElementById("zertifikat-auswahl");
  function zertifikateZeigen() {
    const gewaehlt = Array.from(zertifikatFeld.querySelectorAll("input:checked")).map(function (f) { return f.value; });
    zertifikatFeld.innerHTML = zertifikateFuer(auswahl.value).map(function (zertifikat) {
      const zusatz = zusatzpruefungen(zertifikat, auswahl.value).length;
      return '<label class="auswahl"><input type="checkbox" name="zertifikate" value="' + text(zertifikat.kuerzel) + '"'
        + (gewaehlt.includes(zertifikat.kuerzel) ? " checked" : "") + "> <strong>" + text(zertifikat.kuerzel) + "</strong> "
        + text(zertifikat.bezeichnung) + ' <span class="leise klein">('
        + (zertifikat.herkunft === "eigenes" ? "eigenes" : "Norm")
        + (zusatz > 0 ? ", " + zusatz + (zusatz === 1 ? " zusätzlicher Prüfpunkt" : " zusätzliche Prüfpunkte") : ", ohne zusätzliche Prüfpunkte")
        + ")</span></label>";
    }).join("");
  }

  auswahl.addEventListener("change", function () {
    zertifikateZeigen();
    vorschau();
  });
  zertifikatFeld.addEventListener("change", vorschau);
  formular.menge.addEventListener("input", vorschau);
  zertifikateZeigen();
  vorschau();

  formular.addEventListener("submit", function (ereignis) {
    ereignis.preventDefault();
    const gewaehlt = artikel.find(function (eintrag) { return eintrag.nummer === auswahl.value; });
    const menge = Math.floor(Number(formular.menge.value));
    const auftraege = lesen(SPEICHER_AUFTRAEGE, []);
    // Nächste Auftragsnummer nach FA-1006, der höchsten in den Beispieldaten.
    // Die Nummer eines gelöschten Auftrags wird nicht neu vergeben.
    const laufend = Math.max(1006, Number(lesen(SPEICHER_NUMMER, 1006))) + 1;
    schreiben(SPEICHER_NUMMER, laufend);
    const nummer = "FA-" + laufend;
    auftraege.unshift({
      nummer: nummer,
      artikelnummer: gewaehlt.nummer,
      artikel: gewaehlt.nummer + " " + gewaehlt.bezeichnung,
      menge: menge,
      liefertermin: formular.liefertermin.value,
      kunde: formular.kunde.value.trim(),
      status: ereignis.submitter && ereignis.submitter.value === "freigeben" ? "freigegeben" : "angelegt",
      arbeitsgaenge: gewaehlt.plan.length,
      zertifikate: Array.from(zertifikatFeld.querySelectorAll("input:checked")).map(function (f) { return f.value; }),
      sollZeit: vorkalkulation(gewaehlt, menge).zeit,
      bemerkung: formular.bemerkung.value.trim(),
      plan: arbeitsplanKopie(gewaehlt),
      zuordnungen: {},
      angelegtAm: new Date().toISOString(),
      angelegtVon: angemeldeterName(),
      freigegebenAm: ereignis.submitter && ereignis.submitter.value === "freigeben" ? new Date().toISOString() : null,
    });
    schreiben(SPEICHER_AUFTRAEGE, auftraege);
    window.location.href = "auftraege.html?neu=" + nummer;
  });
}

// ---------- auftraege.html: neue Aufträge anzeigen, Aufträge löschen ----------

// Aktueller Prozess eines neu angelegten Auftrags: der erste Arbeitsgang seines Arbeitsplans
function prozessZelle(auftrag) {
  const artikel = alleArtikel().find(function (eintrag) { return eintrag.nummer === auftrag.artikelnummer; });
  const erster = artikel ? nachAblaufSortiert(artikel.plan)[0] : null;
  const platz = erster ? arbeitsplatz(erster.arbeitsplatz) : null;
  const bereit = auftrag.status === "freigegeben";
  return '<td class="prozess"><strong>' + text(platz ? platz.station : "–") + "</strong> "
    + '<span class="badge ' + (bereit ? "erledigt" : "offen") + '">' + (bereit ? "bereit" : "noch nicht freigegeben") + "</span><br>"
    + '<span class="klein">' + (erster ? "AG 10 " + text(erster.arbeitsgang) + (platz ? " · " + text(platz.bezeichnung) : "") : "") + "</span>"
    + '<div class="balken"><span style="width: 0%;"></span></div><span class="leise klein">0 von ' + auftrag.arbeitsgaenge + " AG erledigt</span></td>";
}

function auftraegeSeite() {
  const tabelle = document.getElementById("auftrags-liste");
  const meldung = document.getElementById("auftraege-meldung");
  const neu = new URLSearchParams(window.location.search).get("neu");
  const geloescht = lesen(SPEICHER_GELOESCHT, []);

  // Beispielaufträge: gelöschte ausblenden, bei noch nicht gestarteten einen Löschen-Knopf ergänzen
  Array.from(tabelle.querySelectorAll("tr")).forEach(function (zeile) {
    const nummer = zeile.querySelector("strong").textContent.trim();
    const beispiel = BEISPIEL_AUFTRAEGE.find(function (auftrag) { return auftrag.nummer === nummer; });
    if (geloescht.includes(nummer)) {
      zeile.remove();
    } else if (beispiel && beispiel.loeschbar) {
      zeile.lastElementChild.insertAdjacentHTML("beforeend", " " + loeschKnopf("data-auftrag-loeschen", nummer, [], "auftrag.anlegen"));
    }
  });

  lesen(SPEICHER_AUFTRAEGE, []).slice().reverse().forEach(function (auftrag) {
    const zeile = document.createElement("tr");
    if (auftrag.nummer === neu) {
      zeile.className = "hervorgehoben";
    }
    const termin = auftrag.liefertermin.split("-").reverse().join(".");
    zeile.innerHTML = "<td><strong>" + text(auftrag.nummer) + '</strong> <span class="leise klein">neu</span></td>'
      + "<td>" + text(auftrag.artikel)
      + (auftrag.zertifikate && auftrag.zertifikate.length ? '<br><span class="leise klein">Zertifikat: ' + text(auftrag.zertifikate.join(", ")) + "</span>" : "")
      + "</td>"
      + "<td>" + (auftrag.kunde ? text(auftrag.kunde) : "–") + "</td>"
      + '<td class="zahl">' + auftrag.menge + "</td>"
      + "<td>" + termin + "</td>"
      + '<td><span class="badge ' + auftrag.status + '">' + auftrag.status + "</span></td>"
      + prozessZelle(auftrag)
      + '<td class="zahl">' + minuten(auftrag.sollZeit) + "</td>"
      + '<td class="zahl">–</td><td><a class="btn btn-klein btn-sekundaer" href="auftrag-detail.html?nr=' + encodeURIComponent(auftrag.nummer) + '">Details</a> '
      + loeschKnopf("data-auftrag-loeschen", auftrag.nummer, [], "auftrag.anlegen") + "</td>";
    tabelle.prepend(zeile);
  });
  rechteAnwenden(tabelle);

  function meldungZeigen(satz) {
    const geaendert = lesen(SPEICHER_AUFTRAEGE, []).length > 0 || lesen(SPEICHER_GELOESCHT, []).length > 0;
    meldung.hidden = !satz && !geaendert;
    meldung.innerHTML = (satz ? "<p><strong>" + text(satz) + "</strong></p>" : "")
      + (geaendert ? '<p class="klein">Angelegte und gelöschte Aufträge speichert im Prototyp nur dieser Browser. '
        + '<a href="auftraege.html" id="auftraege-zuruecksetzen">Beispieldaten wiederherstellen</a></p>' : "");
    const zurueck = document.getElementById("auftraege-zuruecksetzen");
    if (zurueck) {
      zurueck.addEventListener("click", function () {
        schreiben(SPEICHER_AUFTRAEGE, null);
        schreiben(SPEICHER_GELOESCHT, null);
        schreiben(SPEICHER_NUMMER, null);
      });
    }
  }

  tabelle.addEventListener("click", function (ereignis) {
    const knopf = ereignis.target.closest("[data-auftrag-loeschen]");
    if (!knopf || !loeschenBestaetigt(knopf)) {
      return;
    }
    const nummer = knopf.dataset.auftragLoeschen;
    if (BEISPIEL_AUFTRAEGE.some(function (auftrag) { return auftrag.nummer === nummer; })) {
      schreiben(SPEICHER_GELOESCHT, lesen(SPEICHER_GELOESCHT, []).concat(nummer));
    } else {
      schreiben(SPEICHER_AUFTRAEGE, lesen(SPEICHER_AUFTRAEGE, []).filter(function (auftrag) { return auftrag.nummer !== nummer; }));
    }
    knopf.closest("tr").remove();
    meldungZeigen("Auftrag " + nummer + " wurde gelöscht.");
    filter.anwenden();
  });

  const filter = auftragsFilter(tabelle);

  meldungZeigen(neu ? "Auftrag " + neu + " wurde angelegt. Die Arbeitsgänge kommen aus dem Arbeitsplan des Artikels." : "");
}

// Filter der Auftragsliste: Suche links, rechts der Status als Knöpfe mit Anzahl.
// Die Spalte „Aktueller Prozess“ zeigt dann, an welcher Station jedes Teil gerade ist.
function auftragsFilter(tabelle) {
  const formular = document.getElementById("auftrag-filter");
  const ergebnis = document.getElementById("filter-ergebnis");
  const zuruecksetzen = document.getElementById("filter-zuruecksetzen");
  const knoepfe = Array.from(formular.querySelectorAll("[data-status]"));
  const OFFEN = ["angelegt", "freigegeben", "in_arbeit", "messen"];

  // Groß-/Kleinschreibung, Leerzeichen und Bindestriche spielen bei der Suche keine Rolle: „fa1002“ findet FA-1002
  function normal(wert) {
    return wert.toLowerCase().replace(/[\s\-]/g, "");
  }

  // Merkmale jeder Zeile einmal aus der Tabelle lesen
  function zeilen() {
    return Array.from(tabelle.querySelectorAll("tr")).filter(function (zeile) { return !zeile.dataset.leer; }).map(function (zeile) {
      if (!zeile.dataset.status) {
        const badge = zeile.cells[5].querySelector(".badge");
        const klasse = badge ? Array.from(badge.classList).find(function (k) { return k !== "badge"; }) : "";
        zeile.dataset.status = (klasse || "").replace("-", "_");
        zeile.dataset.suchtext = normal(Array.from(zeile.cells).slice(0, 3).map(function (z) { return z.textContent; }).join(" "));
      }
      return zeile;
    });
  }

  function passtStatus(zeile, status) {
    return status === "" || (status === "offen" ? OFFEN.includes(zeile.dataset.status) : zeile.dataset.status === status);
  }

  function anwenden() {
    const suche = normal(formular.suche.value);
    const status = formular.status.value;
    const alle = zeilen();
    let sichtbar = 0;
    alle.forEach(function (zeile) {
      const zeigen = passtStatus(zeile, status) && (!suche || zeile.dataset.suchtext.includes(suche));
      zeile.hidden = !zeigen;
      sichtbar += zeigen ? 1 : 0;
    });

    // Knöpfe: gewählter Status hervorgehoben, Anzahl über alle Aufträge
    knoepfe.forEach(function (knopf) {
      const aktiv = knopf.dataset.status === status;
      knopf.classList.toggle("btn-primaer", aktiv);
      knopf.classList.toggle("btn-sekundaer", !aktiv);
      knopf.setAttribute("aria-pressed", aktiv ? "true" : "false");
      knopf.querySelector(".anzahl").textContent = alle.filter(function (zeile) { return passtStatus(zeile, knopf.dataset.status); }).length;
    });

    let leer = tabelle.querySelector("[data-leer]");
    if (sichtbar === 0 && !leer) {
      leer = document.createElement("tr");
      leer.dataset.leer = "1";
      leer.innerHTML = '<td colspan="10" class="leise">Kein Auftrag passt zum Filter.</td>';
      tabelle.append(leer);
    } else if (sichtbar > 0 && leer) {
      leer.remove();
    }

    const gefiltert = suche || status;
    ergebnis.textContent = gefiltert ? sichtbar + " von " + alle.length + " Aufträgen" : "Alle " + alle.length + " Aufträge";
    zuruecksetzen.hidden = !gefiltert;

    // Auswahl in die Adresse schreiben, damit Zurück, Neuladen und Links den Filter behalten
    try {
      const adresse = new URL(window.location.href);
      ["suche", "status", "art", "verzug"].forEach(function (name) { adresse.searchParams.delete(name); });
      if (formular.suche.value.trim()) { adresse.searchParams.set("suche", formular.suche.value.trim()); }
      if (status) { adresse.searchParams.set("status", status); }
      history.replaceState(null, "", adresse);
    } catch (fehler) {
      // auf file:// nicht möglich, der Filter wirkt trotzdem
    }
  }

  // Startwerte aus der Adresse
  const parameter = new URLSearchParams(window.location.search);
  formular.suche.value = parameter.get("suche") || "";
  formular.status.value = knoepfe.some(function (k) { return k.dataset.status === parameter.get("status"); }) ? parameter.get("status") : "";

  knoepfe.forEach(function (knopf) {
    knopf.addEventListener("click", function () {
      formular.status.value = knopf.dataset.status;
      anwenden();
    });
  });
  formular.suche.addEventListener("input", anwenden);
  formular.addEventListener("submit", function (ereignis) {
    ereignis.preventDefault();
    anwenden();
  });
  zuruecksetzen.addEventListener("click", function () {
    formular.suche.value = "";
    formular.status.value = "";
    anwenden();
  });

  anwenden();
  return { anwenden: anwenden };
}

// ---------- arbeitsplaetze.html: Arbeitsplätze anlegen und bearbeiten ----------

function arbeitsplaetzeSeite() {
  const liste = document.getElementById("platz-liste");
  const formular = document.getElementById("platz-formular");
  const meldung = document.getElementById("platz-meldung");
  const listenMeldung = document.getElementById("platz-liste-meldung");
  let bearbeitet = null;

  formular.station.innerHTML = STATIONSFOLGE.map(function (station) {
    return '<option value="' + station + '">' + station + "</option>";
  }).join("");

  // Wo ein Arbeitsplatz im Arbeitsplan vorkommt
  function verwendetIn(nummer) {
    return alleArtikel().filter(function (artikel) {
      return artikel.plan.some(function (schritt) { return schritt.arbeitsplatz === nummer; });
    }).map(function (artikel) { return artikel.nummer; });
  }

  function listeZeigen() {
    liste.innerHTML = ARBEITSPLAETZE.map(function (platz, index) { return { platz: platz, index: index }; })
      .sort(function (a, b) { return rangImAblauf(a.platz.nummer) - rangImAblauf(b.platz.nummer); })
      .map(function (eintrag) {
        const platz = eintrag.platz;
        const artikel = verwendetIn(platz.nummer);
        return "<tr" + (eintrag.index === bearbeitet ? ' class="hervorgehoben"' : "") + ">"
          + "<td>" + text(platz.nummer) + "</td>"
          + "<td>" + text(platz.bezeichnung) + "</td>"
          + "<td>" + text(platz.station) + (platz.ablauf ? " (" + text(platz.ablauf) + ")" : "") + "</td>"
          + "<td>" + text(platz.kostenstelle || "–") + "</td>"
          + '<td class="zahl">' + zahl(platz.stundensatz, 2) + " €/h</td>"
          + '<td><span class="badge ' + (platz.aktiv !== false ? "aktiv" : "inaktiv") + '">' + (platz.aktiv !== false ? "aktiv" : "inaktiv") + "</span></td>"
          + '<td class="klein">' + (artikel.length ? text(artikel.join(", ")) : '<span class="leise">–</span>') + "</td>"
          + '<td><div class="knopfleiste" style="margin: 0; flex-wrap: nowrap;">'
          + '<button type="button" class="btn btn-klein btn-sekundaer" data-bearbeiten="' + eintrag.index + '">Bearbeiten</button>'
          + loeschKnopf("data-platz-loeschen", eintrag.index, artikel) + "</div></td>"
          + "</tr>";
      }).join("");
  }

  // Löschen nur, wenn kein Arbeitsplan den Arbeitsplatz nutzt. Sonst ist der Knopf gesperrt
  // und nennt die Artikel. Solche Arbeitsplätze lassen sich auf inaktiv setzen.
  function loeschen(index) {
    const platz = ARBEITSPLAETZE[index];
    const artikel = verwendetIn(platz.nummer);
    listenMeldung.hidden = false;
    if (artikel.length > 0) {
      listenMeldung.className = "hinweis fehler";
      listenMeldung.textContent = platz.nummer + " steht noch in den Arbeitsplänen von " + artikel.join(", ")
        + " und bleibt deshalb. Über „Bearbeiten“ lässt er sich auf inaktiv setzen.";
      return;
    }
    ARBEITSPLAETZE.splice(index, 1);
    schreiben(SPEICHER_ARBEITSPLAETZE, ARBEITSPLAETZE);
    meldung.hidden = true;
    bearbeiten(null);
    listenMeldung.hidden = false;
    listenMeldung.className = "hinweis";
    listenMeldung.textContent = "Arbeitsplatz " + platz.nummer + " " + platz.bezeichnung + " wurde gelöscht.";
  }

  function bearbeiten(index) {
    bearbeitet = index;
    const platz = index === null
      ? { nummer: naechsteNummer(), bezeichnung: "", station: "Auslasern", ablauf: "", kostenstelle: "", stundensatz: 0, aktiv: true }
      : ARBEITSPLAETZE[index];
    document.getElementById("platz-titel").textContent = index === null ? "Neuer Arbeitsplatz" : "Arbeitsplatz bearbeiten: " + platz.nummer;
    formular.nummer.value = platz.nummer;
    formular.bezeichnung.value = platz.bezeichnung;
    formular.station.value = platz.station;
    formular.ablauf.value = platz.ablauf || "";
    formular.kostenstelle.value = platz.kostenstelle || "";
    formular.stundensatz.value = index === null ? "" : platz.stundensatz;
    formular.aktiv.checked = platz.aktiv !== false;
    formular.querySelector("[data-ablauf]").hidden = formular.station.value !== "Zerspanung";
    listeZeigen();
  }

  // Vorschlag: die kleinste freie Nummer in Zehnerschritten, z. B. AP-80
  function naechsteNummer() {
    let nummer = 10;
    while (ARBEITSPLAETZE.some(function (platz) { return platz.nummer === "AP-" + nummer; })) {
      nummer += 10;
    }
    return "AP-" + nummer;
  }

  liste.addEventListener("click", function (ereignis) {
    const knopf = ereignis.target.closest("[data-bearbeiten]");
    if (knopf) {
      meldung.hidden = true;
      listenMeldung.hidden = true;
      bearbeiten(Number(knopf.dataset.bearbeiten));
      formular.scrollIntoView({ behavior: "smooth" });
    }

    const loeschKnopfGeklickt = ereignis.target.closest("[data-platz-loeschen]");
    if (loeschKnopfGeklickt && loeschenBestaetigt(loeschKnopfGeklickt)) {
      loeschen(Number(loeschKnopfGeklickt.dataset.platzLoeschen));
    }
  });

  document.getElementById("neuer-platz").addEventListener("click", function () {
    meldung.hidden = true;
    bearbeiten(null);
    formular.scrollIntoView({ behavior: "smooth" });
    formular.bezeichnung.focus();
  });

  formular.station.addEventListener("change", function () {
    formular.querySelector("[data-ablauf]").hidden = formular.station.value !== "Zerspanung";
  });

  formular.addEventListener("submit", function (ereignis) {
    ereignis.preventDefault();
    const nummer = formular.nummer.value.trim().toUpperCase();
    const doppelt = ARBEITSPLAETZE.some(function (platz, index) { return platz.nummer === nummer && index !== bearbeitet; });
    const satz = Number(formular.stundensatz.value);
    meldung.hidden = false;
    meldung.className = "hinweis fehler";
    if (doppelt) {
      meldung.textContent = "Die Nummer " + nummer + " gibt es schon.";
      return;
    }
    if (!(satz > 0)) {
      meldung.textContent = "Bitte einen Stundensatz größer als 0 € eintragen.";
      return;
    }
    if (bearbeitet !== null && !formular.aktiv.checked && verwendetIn(ARBEITSPLAETZE[bearbeitet].nummer).length > 0) {
      meldung.className = "hinweis warnung";
    }
    const vorher = bearbeitet === null ? null : ARBEITSPLAETZE[bearbeitet];
    const platz = {
      nummer: nummer,
      bezeichnung: formular.bezeichnung.value.trim(),
      station: formular.station.value,
      ablauf: formular.station.value === "Zerspanung" ? formular.ablauf.value : "",
      kostenstelle: formular.kostenstelle.value.trim(),
      stundensatz: Math.round(satz * 100) / 100,
      aktiv: formular.aktiv.checked,
    };

    // Neue Nummer: auch in den Arbeitsplänen umstellen, damit die Schritte ihren Arbeitsplatz behalten
    if (vorher && vorher.nummer !== nummer) {
      const artikel = alleArtikel();
      artikel.forEach(function (eintrag) {
        eintrag.plan.forEach(function (schritt) {
          if (schritt.arbeitsplatz === vorher.nummer) {
            schritt.arbeitsplatz = nummer;
          }
        });
      });
      schreiben(SPEICHER_ARTIKEL, artikel);
    }

    if (bearbeitet === null) {
      ARBEITSPLAETZE.push(platz);
      bearbeitet = ARBEITSPLAETZE.length - 1;
    } else {
      ARBEITSPLAETZE[bearbeitet] = platz;
    }
    schreiben(SPEICHER_ARBEITSPLAETZE, ARBEITSPLAETZE);
    document.getElementById("platz-titel").textContent = "Arbeitsplatz bearbeiten: " + platz.nummer;

    let satzText = "Gespeichert.";
    if (vorher && vorher.stundensatz !== platz.stundensatz) {
      satzText += " Der neue Stundensatz gilt nur für neue Aufträge, laufende Aufträge behalten ihren Satz.";
    }
    if (meldung.className.includes("warnung")) {
      satzText += " " + platz.nummer + " ist jetzt inaktiv, steht aber noch in den Arbeitsplänen von "
        + verwendetIn(platz.nummer).join(", ") + ". Für neue Arbeitsschritte wird er nicht mehr angeboten.";
    } else {
      meldung.className = "hinweis";
      if (!vorher) {
        satzText += " " + platz.nummer + " steht jetzt unter „Artikel & Arbeitspläne“ zur Auswahl.";
      }
    }
    meldung.textContent = satzText;
    listeZeigen();
  });

  document.getElementById("plaetze-zuruecksetzen").addEventListener("click", function () {
    schreiben(SPEICHER_ARBEITSPLAETZE, null);
    ARBEITSPLAETZE.splice(0, ARBEITSPLAETZE.length);
    JSON.parse(JSON.stringify(ARBEITSPLAETZE_BEISPIEL)).forEach(function (platz) { ARBEITSPLAETZE.push(platz); });
    meldung.hidden = true;
    bearbeiten(null);
  });

  bearbeiten(null);
}

// ---------- auftrag-detail.html?nr=FA-1007: neu angelegte Aufträge ----------

// Name des angemeldeten Test-Benutzers (aus prototyp.js)
function angemeldeterName() {
  try {
    return BENUTZER[angemeldeteEmail()].name;
  } catch (fehler) {
    return "Arbeitsvorbereitung";
  }
}

function datumText(iso) {
  if (!iso) {
    return "–";
  }
  const d = new Date(iso);
  return isNaN(d) ? iso.split("-").reverse().join(".") : d.toLocaleDateString("de-DE") + (iso.length > 10 ? ", " + d.toLocaleTimeString("de-DE", { hour: "2-digit", minute: "2-digit" }) : "");
}

function auftragDetailSeite(nummer) {
  const inhalt = document.querySelector("main.inhalt");
  const WERKER = ["Fachkraft Lasern", "Fachkraft Kanten", "Fachkraft Schweißen", "Fachkraft Drehen", "Fachkraft Fräsen", "Fachkraft Lackieren", "Fachkraft Versand", "Fachkraft Prüfen", "Fachkraft Sägen", "Fachkraft Montage"];
  let bearbeiten = false;
  let meldung = "";

  function auftraege() {
    return lesen(SPEICHER_AUFTRAEGE, []);
  }

  function speichern(geaendert) {
    schreiben(SPEICHER_AUFTRAEGE, auftraege().map(function (a) { return a.nummer === geaendert.nummer ? geaendert : a; }));
  }

  // Ältere gespeicherte Aufträge ohne Momentaufnahme: Arbeitsplan des Artikels nehmen
  function planVon(auftrag) {
    if (auftrag.plan) {
      return auftrag.plan;
    }
    const artikel = alleArtikel().find(function (a) { return a.nummer === auftrag.artikelnummer; });
    return artikel ? arbeitsplanKopie(artikel) : [];
  }

  function zeigen() {
    const auftrag = auftraege().find(function (a) { return a.nummer === nummer; });
    if (!auftrag) {
      inhalt.innerHTML = '<p class="klein"><a href="auftraege.html">← Zurück zu den Aufträgen</a></p>'
        + '<div class="hinweis fehler"><p>Den Auftrag ' + text(nummer) + " gibt es in diesem Browser nicht (mehr).</p></div>";
      return;
    }
    const plan = planVon(auftrag);
    const punkte = pruefpunkteFuer(auftrag.artikelnummer, plan, auftrag.zertifikate || []);
    const aenderbar = ["angelegt", "freigegeben"].includes(auftrag.status);
    const zuordnungen = auftrag.zuordnungen || {};
    const ohneWerker = plan.filter(function (s, i) { return !(zuordnungen[(i + 1) * 10] || []).length; }).length;

    inhalt.innerHTML = '<p class="klein"><a href="auftraege.html">← Zurück zu den Aufträgen</a></p>'
      + '<div class="seitenkopf"><div>'
      + "<h1>Auftrag " + text(auftrag.nummer) + ' <span class="badge ' + auftrag.status + '">' + auftrag.status + "</span></h1>"
      + '<p class="leise">' + text(auftrag.artikel) + " · " + auftrag.menge + " Stück · Liefertermin " + datumText(auftrag.liefertermin) + "</p>"
      + '</div><div class="knopfleiste">'
      + (auftrag.status === "angelegt" ? '<button type="button" class="btn btn-primaer" data-aktion="freigeben" data-recht-js="auftrag.freigeben">Freigeben</button>' : "")
      + (aenderbar && !bearbeiten ? '<button type="button" class="btn btn-sekundaer" data-aktion="bearbeiten" data-recht-js="auftrag.anlegen">Bearbeiten</button>' : "")
      + '<button type="button" class="btn btn-gefahr" data-aktion="loeschen" data-recht-js="auftrag.anlegen">Löschen</button>'
      + "</div></div>"
      + (meldung ? '<div class="hinweis" role="status">' + text(meldung) + "</div>" : "")
      + (auftrag.status === "freigegeben" && ohneWerker ? '<div class="hinweis warnung"><p>' + ohneWerker + (ohneWerker === 1 ? " Arbeitsgang hat" : " Arbeitsgänge haben")
        + " noch keinen Werker. Ohne Zuordnung sehen die Werker " + (ohneWerker === 1 ? "ihn" : "sie") + " nicht im Terminal.</p></div>" : "")
      + (bearbeiten ? bearbeitenFormular(auftrag) : "")
      + '<div class="spalten-gleich"><section class="karte"><h2>Kopfdaten</h2><dl class="kopfdaten">'
      + "<div><dt>Kunde</dt><dd>" + text(auftrag.kunde || "–") + "</dd></div>"
      + "<div><dt>Angelegt</dt><dd>" + datumText(auftrag.angelegtAm) + (auftrag.angelegtVon ? ", " + text(auftrag.angelegtVon) : "") + "</dd></div>"
      + "<div><dt>Freigegeben</dt><dd>" + datumText(auftrag.freigegebenAm) + "</dd></div>"
      + "<div><dt>Bemerkung</dt><dd>" + text(auftrag.bemerkung || "–") + "</dd></div>"
      + '</dl></section><section class="karte"><h2>Geforderte Zertifikate</h2>'
      + ((auftrag.zertifikate || []).length ? auftrag.zertifikate.map(function (k) {
        const z = alleZertifikate().find(function (x) { return x.kuerzel === k; });
        return "<p><strong>" + text(k) + "</strong> " + text(z ? z.bezeichnung : "") + ' <span class="badge offen">gefordert</span></p>';
      }).join("") : '<p class="leise">Der Kunde fordert kein Zertifikat.</p>')
      + "</section></div>"
      + '<section class="karte"><h2>Arbeitsgänge</h2><div class="tabelle-scroll"><table><thead><tr>'
      + '<th>AG</th><th>Arbeitsgang</th><th>Station</th><th>Arbeitsplatz</th><th class="zahl">Soll-Zeit</th><th class="zahl">Soll-Kosten</th><th>Status</th><th>Werker</th>'
      + "</tr></thead><tbody>"
      + plan.map(function (schritt, i) {
        const soll = schritt.ruestzeit + auftrag.menge * schritt.stueckzeit;
        const werker = zuordnungen[(i + 1) * 10] || [];
        const bereit = auftrag.status === "freigegeben" && i === 0;
        return "<tr><td>" + (i + 1) * 10 + "</td><td>" + text(schritt.arbeitsgang) + "</td><td>" + text(schritt.station || "") + "</td>"
          + "<td>" + text(schritt.platzName) + " (" + text(schritt.arbeitsplatz) + ")</td>"
          + '<td class="zahl">' + minuten(soll) + '</td><td class="zahl">' + zahl(soll / 60 * schritt.stundensatz, 2) + " €</td>"
          + '<td><span class="badge ' + (bereit ? "erledigt" : "offen") + '">' + (bereit ? "bereit" : "offen") + "</span></td>"
          + "<td>" + (werker.length ? werker.map(function (w) {
            return text(w) + ' <button type="button" class="btn btn-klein btn-sekundaer" data-recht-js="auftrag.zuordnen" data-entziehen="' + (i + 1) * 10 + '|' + text(w) + '">Entfernen</button>';
          }).join("<br>") : '<span class="leise">noch niemand</span>') + "</td></tr>";
      }).join("")
      + '</tbody><tfoot><tr><td colspan="4">Summe</td><td class="zahl">' + minuten(sollZeitVon(plan, auftrag.menge)) + "</td><td colspan=\"3\"></td></tr></tfoot></table></div>"
      + '<div class="knopfleiste" data-recht-js="auftrag.zuordnen" style="margin-top: 1rem;">'
      + '<label for="zuordnung-ag" class="leise" style="margin: 0; align-self: center;">Werker zuordnen</label>'
      + '<select id="zuordnung-ag" style="width: auto;">' + plan.map(function (s, i) { return '<option value="' + (i + 1) * 10 + '">AG ' + (i + 1) * 10 + " " + text(s.arbeitsgang) + "</option>"; }).join("") + "</select>"
      + '<select id="zuordnung-werker" style="width: auto;">' + WERKER.map(function (w) { return "<option>" + w + "</option>"; }).join("") + "</select>"
      + '<button type="button" class="btn btn-primaer" data-aktion="zuordnen">Zuordnen</button></div></section>'
      + '<section class="karte"><h2>Checkliste für den Werker</h2><div class="tabelle-scroll"><table><thead><tr><th>AG</th><th>Prüfpunkt</th><th>Soll / Toleranz</th><th>Grund</th></tr></thead><tbody>'
      + (punkte.flatMap(function (liste, i) {
        return liste.map(function (p) {
          const m = p.merkmal;
          return "<tr><td>" + (i + 1) * 10 + "</td><td>" + text(m.bezeichnung) + "</td><td>"
            + (m.art === "messwert" ? zahl(m.soll === null ? 0 : m.soll, 3) + " " + text(m.einheit || "") + " (" + zahl(m.unten, 3) + " bis " + zahl(m.oben, 3) + ")" : "Sichtprüfung")
            + "</td><td>" + (p.kuerzel ? '<span class="badge freigegeben">' + text(p.kuerzel) + "</span>" : "immer") + "</td></tr>";
        });
      }).join("") || '<tr><td colspan="4" class="leise">Keine Prüfpunkte.</td></tr>')
      + "</tbody></table></div></section>";
    rechteAnwenden(inhalt);
  }

  function bearbeitenFormular(auftrag) {
    const heute = new Date().toISOString().slice(0, 10);
    return '<form class="karte" id="auftrag-aendern"><h2>Auftrag bearbeiten</h2>'
      + '<p class="leise klein">Geht nur, solange noch niemand an dem Auftrag gearbeitet hat. Soll-Zeiten und Checkliste passen sich an.</p>'
      + '<p class="hinweis fehler" id="aendern-fehler" hidden></p><div class="formular-raster">'
      + '<div class="feld"><label for="a-menge">Menge (Stück)</label><input type="number" id="a-menge" min="1" step="1" value="' + auftrag.menge + '" required></div>'
      + '<div class="feld"><label for="a-termin">Liefertermin</label><input type="date" id="a-termin" min="' + heute + '" value="' + text(auftrag.liefertermin) + '" required></div>'
      + '<div class="feld"><label for="a-kunde">Kunde oder Kommission</label><input type="text" id="a-kunde" value="' + text(auftrag.kunde || "") + '"></div>'
      + '<div class="feld"><label for="a-bemerkung">Bemerkung</label><input type="text" id="a-bemerkung" value="' + text(auftrag.bemerkung || "") + '"></div>'
      + '</div><fieldset><legend>Zertifikate, die der Kunde fordert</legend><div class="auswahl-gruppe">'
      + zertifikateFuer(auftrag.artikelnummer).map(function (z) {
        return '<label class="auswahl"><input type="checkbox" name="a-zertifikat" value="' + text(z.kuerzel) + '"' + ((auftrag.zertifikate || []).includes(z.kuerzel) ? " checked" : "")
          + "> <strong>" + text(z.kuerzel) + "</strong> " + text(z.bezeichnung) + "</label>";
      }).join("")
      + '</div></fieldset><div class="knopfleiste" style="margin: 0;">'
      + '<button type="submit" class="btn btn-primaer">Änderungen speichern</button>'
      + '<button type="button" class="btn btn-sekundaer" data-aktion="abbrechen">Abbrechen</button></div></form>';
  }

  inhalt.addEventListener("click", function (ereignis) {
    const knopf = ereignis.target.closest("button");
    if (!knopf) {
      return;
    }
    const auftrag = auftraege().find(function (a) { return a.nummer === nummer; });
    const aktion = knopf.dataset.aktion;
    meldung = "";
    if (aktion === "freigeben") {
      auftrag.status = "freigegeben";
      auftrag.freigegebenAm = new Date().toISOString();
      speichern(auftrag);
      meldung = "Auftrag " + nummer + " ist freigegeben. Jetzt den Arbeitsgängen Werker zuordnen.";
    } else if (aktion === "bearbeiten") {
      bearbeiten = true;
    } else if (aktion === "abbrechen") {
      bearbeiten = false;
    } else if (aktion === "loeschen") {
      if (!loeschenBestaetigt(knopf)) {
        return;
      }
      schreiben(SPEICHER_AUFTRAEGE, auftraege().filter(function (a) { return a.nummer !== nummer; }));
      window.location.href = "auftraege.html";
      return;
    } else if (aktion === "zuordnen") {
      const ag = document.getElementById("zuordnung-ag").value;
      const werker = document.getElementById("zuordnung-werker").value;
      auftrag.zuordnungen = auftrag.zuordnungen || {};
      auftrag.zuordnungen[ag] = (auftrag.zuordnungen[ag] || []).filter(function (w) { return w !== werker; }).concat(werker);
      speichern(auftrag);
      meldung = werker + " ist AG " + ag + " zugeordnet.";
    } else if (knopf.dataset.entziehen) {
      const teile = knopf.dataset.entziehen.split("|");
      auftrag.zuordnungen[teile[0]] = (auftrag.zuordnungen[teile[0]] || []).filter(function (w) { return w !== teile[1]; });
      speichern(auftrag);
    } else {
      return;
    }
    zeigen();
  });

  inhalt.addEventListener("submit", function (ereignis) {
    if (ereignis.target.id !== "auftrag-aendern") {
      return;
    }
    ereignis.preventDefault();
    const auftrag = auftraege().find(function (a) { return a.nummer === nummer; });
    const menge = Math.floor(Number(document.getElementById("a-menge").value));
    const termin = document.getElementById("a-termin").value;
    const fehler = document.getElementById("aendern-fehler");
    if (!(menge >= 1) || !termin || termin < new Date().toISOString().slice(0, 10)) {
      fehler.hidden = false;
      fehler.textContent = !(menge >= 1) ? "Die Menge muss mindestens 1 sein." : "Der Liefertermin darf nicht in der Vergangenheit liegen.";
      return;
    }
    auftrag.menge = menge;
    auftrag.liefertermin = termin;
    auftrag.kunde = document.getElementById("a-kunde").value.trim();
    auftrag.bemerkung = document.getElementById("a-bemerkung").value.trim();
    auftrag.zertifikate = Array.from(document.querySelectorAll('[name="a-zertifikat"]:checked')).map(function (f) { return f.value; });
    auftrag.plan = planVon(auftrag);
    auftrag.sollZeit = sollZeitVon(auftrag.plan, menge);
    speichern(auftrag);
    bearbeiten = false;
    meldung = "Auftrag " + nummer + " geändert. Soll-Zeiten und Checkliste sind angepasst.";
    zeigen();
  });

  zeigen();
}

// ---------- Start ----------

if (document.getElementById("artikel-formular")) {
  artikelSeite();
} else if (document.getElementById("zertifikat-liste")) {
  zertifikateSeite();
} else if (document.getElementById("auftrag-formular")) {
  auftragAnlegenSeite();
} else if (document.getElementById("auftrags-liste")) {
  auftraegeSeite();
} else if (document.getElementById("platz-liste")) {
  arbeitsplaetzeSeite();
} else if (seiteVon(window.location.href) === "auftrag-detail.html" && new URLSearchParams(window.location.search).get("nr")) {
  auftragDetailSeite(new URLSearchParams(window.location.search).get("nr"));
}
