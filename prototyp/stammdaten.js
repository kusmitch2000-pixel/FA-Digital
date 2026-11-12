// Nur für den Prototyp: Stammdaten zum Ausprobieren auf der Website.
// Arbeitsplätze, Artikel mit Arbeitsplan, Zertifikate und neu angelegte Aufträge. Änderungen
// merkt sich der Browser (localStorage), jeder Besucher hat also seinen eigenen Stand.
// Die Beispieldaten sind dieselben wie in datenbank/02_testdaten.sql (Laravel-App).
//
// Seiten:
// - artikel.html:          Artikel anlegen und jedem Artikel seine Arbeitsschritte zuordnen
// - zertifikate.html:      eigene Zertifikate für bestimmte Artikel anlegen (Arbeitsvorbereitung)
// - auftrag-anlegen.html:  Artikel, Menge, Liefertermin und Zertifikate wählen, Vorschau rechnet live
// - auftraege.html:        zeigt die neu angelegten Aufträge oben in der Liste

// Stationen, die ein Rohteil durchläuft, mit den Arbeitsplätzen dazu
const ARBEITSPLAETZE = [
  { nummer: "AP-10", bezeichnung: "Laserschneidanlage", station: "Auslasern", stundensatz: 110 },
  { nummer: "AP-20", bezeichnung: "Abkantpresse", station: "Umformen", stundensatz: 75 },
  { nummer: "AP-30", bezeichnung: "Schweißplatz", station: "Bearbeitung", stundensatz: 65 },
  { nummer: "AP-40", bezeichnung: "CNC-Drehmaschine", station: "Zerspanung", ablauf: "Drehen", stundensatz: 85 },
  { nummer: "AP-50", bezeichnung: "CNC-Fräsmaschine", station: "Zerspanung", ablauf: "Fräsen", stundensatz: 95 },
  { nummer: "AP-60", bezeichnung: "Lackieranlage", station: "Lackierung", stundensatz: 70 },
  { nummer: "AP-70", bezeichnung: "Versandplatz", station: "Versand", stundensatz: 40 },
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
      { arbeitsgang: "Kanten", arbeitsplatz: "AP-20", ruestzeit: 20, stueckzeit: 3 },
      { arbeitsgang: "Lackieren", arbeitsplatz: "AP-60", ruestzeit: 25, stueckzeit: 4 },
      { arbeitsgang: "Verpacken und versenden", arbeitsplatz: "AP-70", ruestzeit: 10, stueckzeit: 0.5 },
    ],
  },
  {
    nummer: "V-420", bezeichnung: "Späneschutzwanne", art: "Verkleidungsteil",
    rohteil: "Blech 1.4301, 3 mm", aktiv: true,
    plan: [
      { arbeitsgang: "Auslasern", arbeitsplatz: "AP-10", ruestzeit: 15, stueckzeit: 3 },
      { arbeitsgang: "Kanten zur Wanne", arbeitsplatz: "AP-20", ruestzeit: 25, stueckzeit: 6 },
      { arbeitsgang: "Ecken verschweißen", arbeitsplatz: "AP-30", ruestzeit: 20, stueckzeit: 12 },
      { arbeitsgang: "Lackieren", arbeitsplatz: "AP-60", ruestzeit: 25, stueckzeit: 5 },
      { arbeitsgang: "Verpacken und versenden", arbeitsplatz: "AP-70", ruestzeit: 10, stueckzeit: 1 },
    ],
  },
  {
    nummer: "V-430", bezeichnung: "Schutztür mit Sichtfenster", art: "Verkleidungsteil",
    rohteil: "Blech DC01, 2 mm", aktiv: true,
    plan: [
      { arbeitsgang: "Auslasern", arbeitsplatz: "AP-10", ruestzeit: 15, stueckzeit: 4 },
      { arbeitsgang: "Kanten", arbeitsplatz: "AP-20", ruestzeit: 20, stueckzeit: 5 },
      { arbeitsgang: "Scharniere anschweißen", arbeitsplatz: "AP-30", ruestzeit: 15, stueckzeit: 8 },
      { arbeitsgang: "Fenstersitz fräsen", arbeitsplatz: "AP-50", ruestzeit: 30, stueckzeit: 6 },
      { arbeitsgang: "Lackieren", arbeitsplatz: "AP-60", ruestzeit: 25, stueckzeit: 5 },
      { arbeitsgang: "Verpacken und versenden", arbeitsplatz: "AP-70", ruestzeit: 10, stueckzeit: 1 },
    ],
  },
  {
    nummer: "F-310", bezeichnung: "Flansch DN80", art: "Zerspanteil",
    rohteil: "Blech S235, 20 mm", aktiv: true,
    plan: [
      { arbeitsgang: "Ronde auslasern", arbeitsplatz: "AP-10", ruestzeit: 10, stueckzeit: 2 },
      { arbeitsgang: "Drehen", arbeitsplatz: "AP-40", ruestzeit: 25, stueckzeit: 3 },
      { arbeitsgang: "Lochkreis fräsen", arbeitsplatz: "AP-50", ruestzeit: 40, stueckzeit: 5 },
      { arbeitsgang: "Verpacken und versenden", arbeitsplatz: "AP-70", ruestzeit: 10, stueckzeit: 0.5 },
    ],
  },
  {
    nummer: "W-200", bezeichnung: "Antriebswelle Ø40", art: "Zerspanteil",
    rohteil: "Rundstahl Ø45, auf Länge", aktiv: true,
    plan: [
      { arbeitsgang: "Drehen", arbeitsplatz: "AP-40", ruestzeit: 30, stueckzeit: 4 },
      { arbeitsgang: "Passfedernut fräsen", arbeitsplatz: "AP-50", ruestzeit: 20, stueckzeit: 2 },
      { arbeitsgang: "Verpacken und versenden", arbeitsplatz: "AP-70", ruestzeit: 10, stueckzeit: 0.5 },
    ],
  },
];

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

// Version im Namen: ältere Stände mit anderen Arbeitsplatznummern werden nicht mehr gelesen
const SPEICHER_ARTIKEL = "fa-digital-artikel-v2";
const SPEICHER_ZERTIFIKATE = "fa-digital-zertifikate-v1";
const SPEICHER_AUFTRAEGE = "fa-digital-auftraege-v2";

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

function arbeitsplatzAuswahl(gewaehlt) {
  const gruppen = {};
  ARBEITSPLAETZE.forEach(function (platz) {
    (gruppen[platz.station] = gruppen[platz.station] || []).push(platz);
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
        + "<td>" + (eigen ? '<button type="button" class="btn btn-klein btn-sekundaer" data-recht-js="zertifikat.anlegen" data-bearbeiten="' + index + '">Bearbeiten</button>' : '<span class="leise klein">Norm</span>') + "</td>"
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
    meldung.hidden = true;
    listeZeigen();
  }

  liste.addEventListener("click", function (ereignis) {
    const knopf = ereignis.target.closest("[data-bearbeiten]");
    if (knopf) {
      bearbeiten(Number(knopf.dataset.bearbeiten));
      formular.scrollIntoView({ behavior: "smooth" });
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
        + '<td><button type="button" class="btn btn-klein btn-sekundaer" data-bearbeiten="' + index + '">Bearbeiten</button></td>'
        + "</tr>";
    }).join("");
  }

  function planZeigen(plan) {
    planZeilen.innerHTML = plan.map(function (schritt, index) {
      return "<tr>"
        + '<td class="zahl">' + (index + 1) * 10 + "</td>"
        + '<td><input type="text" data-feld="arbeitsgang" value="' + text(schritt.arbeitsgang) + '" aria-label="Arbeitsgang" required style="min-width: 14rem;"></td>'
        + '<td><select data-feld="arbeitsplatz" aria-label="Station und Arbeitsplatz" style="min-width: 15rem;">' + arbeitsplatzAuswahl(schritt.arbeitsplatz) + "</select></td>"
        + '<td><input type="number" data-feld="ruestzeit" value="' + schritt.ruestzeit + '" min="0" step="1" aria-label="Rüstzeit" style="width: 5.5rem;"></td>'
        + '<td><input type="number" data-feld="stueckzeit" value="' + schritt.stueckzeit + '" min="0" step="0.1" aria-label="Stückzeit" style="width: 5.5rem;"></td>'
        + '<td><div class="knopfleiste" style="margin: 0; flex-wrap: nowrap;">'
        + '<button type="button" class="btn btn-klein btn-sekundaer" data-hoch="' + index + '" aria-label="nach oben"' + (index === 0 ? " disabled" : "") + ">↑</button>"
        + '<button type="button" class="btn btn-klein btn-sekundaer" data-runter="' + index + '" aria-label="nach unten"' + (index === plan.length - 1 ? " disabled" : "") + ">↓</button>"
        + '<button type="button" class="btn btn-klein btn-gefahr" data-entfernen="' + index + '">Entfernen</button>'
        + "</div></td></tr>";
    }).join("") || '<tr><td colspan="6" class="leise">Noch keine Arbeitsschritte. Unten eine Station hinzufügen.</td></tr>';
  }

  // Den Arbeitsplan so auslesen, wie er gerade im Formular steht
  function planLesen() {
    return Array.from(planZeilen.querySelectorAll("tr")).filter(function (zeile) {
      return zeile.querySelector("input");
    }).map(function (zeile) {
      return {
        arbeitsgang: zeile.querySelector('[data-feld="arbeitsgang"]').value.trim(),
        arbeitsplatz: zeile.querySelector('[data-feld="arbeitsplatz"]').value,
        ruestzeit: Number(zeile.querySelector('[data-feld="ruestzeit"]').value) || 0,
        stueckzeit: Number(zeile.querySelector('[data-feld="stueckzeit"]').value) || 0,
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
    planZeigen(eintrag.plan);
    meldung.hidden = true;
    listeZeigen();
  }

  liste.addEventListener("click", function (ereignis) {
    const knopf = ereignis.target.closest("[data-bearbeiten]");
    if (knopf) {
      bearbeiten(Number(knopf.dataset.bearbeiten));
      formular.scrollIntoView({ behavior: "smooth" });
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
    } else if (knopf.dataset.hoch !== undefined) {
      const i = Number(knopf.dataset.hoch);
      plan.splice(i - 1, 0, plan.splice(i, 1)[0]);
    } else if (knopf.dataset.runter !== undefined) {
      const i = Number(knopf.dataset.runter);
      plan.splice(i + 1, 0, plan.splice(i, 1)[0]);
    }
    planZeigen(plan);
  });

  // Ein Knopf je Station: hängt einen passenden Arbeitsschritt an
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
    plan.push({ arbeitsgang: vorlage.arbeitsgang, arbeitsplatz: vorlage.arbeitsplatz, ruestzeit: 0, stueckzeit: 0 });
    planZeigen(plan);
  });

  formular.addEventListener("submit", function (ereignis) {
    ereignis.preventDefault();
    const nummer = formular.nummer.value.trim().toUpperCase();
    const doppelt = artikel.some(function (eintrag, index) { return eintrag.nummer === nummer && index !== bearbeitet; });
    const plan = planLesen();
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
    meldung.textContent = "Gespeichert. Der Arbeitsplan gilt für alle neuen Aufträge über " + eintrag.nummer + ".";
    listeZeigen();
  });

  document.getElementById("beispiel-laden").addEventListener("click", function () {
    schreiben(SPEICHER_ARTIKEL, null);
    artikel = alleArtikel();
    bearbeiten(0);
  });

  bearbeiten(0);
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

  function vorschau() {
    const gewaehlt = artikel.find(function (eintrag) { return eintrag.nummer === auswahl.value; });
    const menge = Math.max(0, Math.floor(Number(formular.menge.value) || 0));
    if (!gewaehlt) {
      return;
    }
    const rechnung = vorkalkulation(gewaehlt, menge);
    document.getElementById("vorschau-titel").textContent =
      "Für " + gewaehlt.nummer + " " + gewaehlt.bezeichnung + ", " + menge + " Stück · Rohteil: " + gewaehlt.rohteil;
    document.getElementById("vorschau-zeilen").innerHTML = rechnung.zeilen.map(function (zeile) {
      return "<tr><td>" + zeile.ag + "</td>"
        + "<td>" + text(zeile.arbeitsgang) + "</td>"
        + "<td>" + text(zeile.platz.station) + "</td>"
        + "<td>" + text(zeile.platz.bezeichnung) + " (" + zeile.platz.nummer + ")</td>"
        + '<td class="zahl">' + minuten(zeile.ruestzeit) + "</td>"
        + '<td class="zahl">' + minuten(zeile.stueckzeit) + "</td>"
        + '<td class="zahl">' + zahl(zeile.ruestzeit, 0) + " + " + menge + " × " + zahl(zeile.stueckzeit, 1) + " = <strong>" + minuten(zeile.sollZeit) + "</strong></td>"
        + '<td class="zahl">' + zahl(zeile.platz.stundensatz, 2) + " €/h</td>"
        + '<td class="zahl">' + zahl(zeile.sollKosten, 2) + " €</td></tr>";
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
    vorschau();
    zertifikateZeigen();
  });
  formular.menge.addEventListener("input", vorschau);
  vorschau();
  zertifikateZeigen();

  formular.addEventListener("submit", function (ereignis) {
    ereignis.preventDefault();
    const gewaehlt = artikel.find(function (eintrag) { return eintrag.nummer === auswahl.value; });
    const menge = Math.floor(Number(formular.menge.value));
    const auftraege = lesen(SPEICHER_AUFTRAEGE, []);
    // Nächste Auftragsnummer nach FA-1006, der höchsten in den Beispieldaten
    const nummer = "FA-" + (1007 + auftraege.length);
    auftraege.unshift({
      nummer: nummer,
      artikel: gewaehlt.nummer + " " + gewaehlt.bezeichnung,
      menge: menge,
      liefertermin: formular.liefertermin.value,
      kunde: formular.kunde.value.trim(),
      status: ereignis.submitter && ereignis.submitter.value === "freigeben" ? "freigegeben" : "angelegt",
      arbeitsgaenge: gewaehlt.plan.length,
      zertifikate: Array.from(zertifikatFeld.querySelectorAll("input:checked")).map(function (f) { return f.value; }),
      sollZeit: vorkalkulation(gewaehlt, menge).zeit,
    });
    schreiben(SPEICHER_AUFTRAEGE, auftraege);
    window.location.href = "auftraege.html?neu=" + nummer;
  });
}

// ---------- auftraege.html: neu angelegte Aufträge oben anzeigen ----------

function auftraegeSeite() {
  const auftraege = lesen(SPEICHER_AUFTRAEGE, []);
  const tabelle = document.querySelector("main table tbody");
  if (!tabelle || auftraege.length === 0) {
    return;
  }
  const neu = new URLSearchParams(window.location.search).get("neu");

  auftraege.slice().reverse().forEach(function (auftrag) {
    const zeile = document.createElement("tr");
    if (auftrag.nummer === neu) {
      zeile.className = "hervorgehoben";
    }
    const termin = auftrag.liefertermin.split("-").reverse().join(".");
    zeile.innerHTML = "<td><strong>" + text(auftrag.nummer) + '</strong> <span class="leise klein">neu</span></td>'
      + "<td>" + text(auftrag.artikel)
      + (auftrag.zertifikate && auftrag.zertifikate.length ? '<br><span class="leise klein">Zertifikat: ' + text(auftrag.zertifikate.join(", ")) + "</span>" : "")
      + "</td>"
      + '<td class="zahl">' + auftrag.menge + "</td>"
      + "<td>" + termin + "</td>"
      + '<td><span class="badge ' + auftrag.status + '">' + auftrag.status + "</span></td>"
      + '<td><div class="balken"><span style="width: 0%;"></span></div><span class="leise klein">0 von ' + auftrag.arbeitsgaenge + " AG</span></td>"
      + '<td class="zahl">' + minuten(auftrag.sollZeit) + "</td>"
      + '<td class="zahl">–</td><td></td>';
    tabelle.prepend(zeile);
  });

  const hinweis = document.createElement("div");
  hinweis.className = "hinweis";
  hinweis.innerHTML = (neu ? "<p><strong>Auftrag " + text(neu) + " wurde angelegt.</strong> Die Arbeitsgänge kommen aus dem Arbeitsplan des Artikels.</p>" : "")
    + '<p class="klein">Im Prototyp angelegte Aufträge speichert nur dieser Browser. '
    + '<a href="auftraege.html" id="auftraege-zuruecksetzen">Wieder entfernen</a></p>';
  tabelle.closest("section").before(hinweis);
  document.getElementById("auftraege-zuruecksetzen").addEventListener("click", function () {
    schreiben(SPEICHER_AUFTRAEGE, null);
  });
}

// ---------- Start ----------

if (document.getElementById("artikel-formular")) {
  artikelSeite();
} else if (document.getElementById("zertifikat-liste")) {
  zertifikateSeite();
} else if (document.getElementById("auftrag-formular")) {
  auftragAnlegenSeite();
} else if (document.querySelector("main table tbody")) {
  auftraegeSeite();
}
