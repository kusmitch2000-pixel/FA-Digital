// Nur für den Prototyp. Mit PHP bzw. Laravel (Ordner laravel/) fällt die Datei weg.
//
// 1. Formulare: Statische Webserver wie GitHub Pages nehmen keine Formulare mit method="post"
//    an (Fehler 405). Das Skript fängt das Absenden ab und öffnet die Zielseite aus "action".
// 2. Rollen: Die Anmeldung merkt sich den Test-Benutzer im Browser. Jede Seite zeigt oben Name
//    und Rolle, blendet fremde Menüpunkte und Aktionen aus und sperrt Seiten der anderen Rollen.
//    Das ist nur Darstellung, kein Schutz. Die echte Prüfung macht die Laravel-App.

// Test-Benutzer wie in datenbank/02_testdaten.sql. Das Passwort prüft der Prototyp nicht.
const BENUTZER = {
  "sven.petersen@fa-digital.de": { name: "Sven Petersen", rolle: "admin" },
  "anna.becker@fa-digital.de": { name: "Anna Becker", rolle: "arbeitsvorbereitung" },
  "lena.hoffmann@fa-digital.de": { name: "Lena Hoffmann", rolle: "meister" },
  "tobias.krueger@fa-digital.de": { name: "Tobias Krüger", rolle: "controlling" },
  "jonas.wolff@fa-digital.de": { name: "Jonas Wolff", rolle: "werker" },
  "mehmet.yilmaz@fa-digital.de": { name: "Mehmet Yilmaz", rolle: "werker" },
  "paul.schneider@fa-digital.de": { name: "Paul Schneider", rolle: "werker" },
  "katrin.wagner@fa-digital.de": { name: "Katrin Wagner", rolle: "werker", gesperrt: true },
};

const ROLLEN = {
  werker: "Werker",
  meister: "Meister",
  arbeitsvorbereitung: "Arbeitsvorbereitung",
  controlling: "Controlling",
  admin: "Admin",
};

// Wer was darf, genauso wie in laravel/config/fa.php
const RECHTE = {
  "terminal": ["werker", "meister", "admin"],
  "halle": ["meister", "arbeitsvorbereitung", "controlling", "admin"],
  "auftraege": ["werker", "meister", "arbeitsvorbereitung", "controlling", "admin"],
  "auftrag.anlegen": ["arbeitsvorbereitung", "admin"],
  "auftrag.freigeben": ["arbeitsvorbereitung", "admin"],
  "buchung.korrigieren": ["meister", "admin"],
  "nachkalkulation": ["meister", "controlling", "admin"],
  "nachkalkulation.abschliessen": ["controlling", "admin"],
  "auswertung": ["meister", "controlling", "admin"],
  "arbeitsplaene": ["arbeitsvorbereitung", "admin"],
  "arbeitsplaetze": ["arbeitsvorbereitung", "controlling", "admin"],
  "benutzer": ["admin"],
};

// Welches Recht eine Seite braucht
const SEITEN = {
  "werker.html": "terminal",
  "dashboard.html": "halle",
  "auftraege.html": "auftraege",
  "auftrag-detail.html": "auftraege",
  "auftrag-anlegen.html": "auftrag.anlegen",
  "nachkalkulation.html": "nachkalkulation",
  "auswertung.html": "auswertung",
  "artikel.html": "arbeitsplaene",
  "arbeitsplaetze.html": "arbeitsplaetze",
  "benutzer.html": "benutzer",
};

// Startseite nach der Anmeldung
const STARTSEITE = {
  werker: "werker.html",
  meister: "dashboard.html",
  arbeitsvorbereitung: "auftraege.html",
  controlling: "nachkalkulation.html",
  admin: "dashboard.html",
};

const SPEICHER = "fa-digital-benutzer";

// Ohne Anmeldung (z. B. über einen direkten Link) gilt wie bisher der Admin
function angemeldeteEmail() {
  try {
    const email = localStorage.getItem(SPEICHER);
    if (email && BENUTZER[email] && !BENUTZER[email].gesperrt) {
      return email;
    }
  } catch (fehler) {
    // Speicher im Browser gesperrt, z. B. im privaten Fenster
  }
  return "sven.petersen@fa-digital.de";
}

function merken(email) {
  try {
    if (email) {
      localStorage.setItem(SPEICHER, email);
    } else {
      localStorage.removeItem(SPEICHER);
    }
  } catch (fehler) {
    // ohne Speicher bleibt es beim Admin
  }
}

function darf(rolle, recht) {
  return (RECHTE[recht] || []).includes(rolle);
}

// Dateiname einer Seite, z. B. "auftraege.html" aus "auftraege.html?status=fertig"
function seiteVon(adresse) {
  const pfad = new URL(adresse, window.location.href).pathname;
  return pfad.split("/").pop() || "index.html";
}

function darfSeite(rolle, seite) {
  return !SEITEN[seite] || darf(rolle, SEITEN[seite]);
}

// ---------- Anmeldeseite ----------

function anmelden(formular) {
  const email = formular.email.value.trim().toLowerCase();
  const benutzer = BENUTZER[email];
  const meldung = document.getElementById("anmeldung-fehler");

  if (!benutzer || benutzer.gesperrt) {
    meldung.textContent = benutzer
      ? benutzer.name + " ist gesperrt und kann sich nicht anmelden."
      : "Diese E-Mail kennt der Prototyp nicht. Bitte einen Test-Benutzer wählen.";
    meldung.hidden = false;
    return;
  }

  merken(email);
  window.location.href = STARTSEITE[benutzer.rolle];
}

function anmeldeseiteEinrichten() {
  const formular = document.getElementById("anmeldung");

  document.querySelectorAll("[data-email]").forEach(function (knopf) {
    knopf.addEventListener("click", function () {
      formular.email.value = knopf.dataset.email;
      anmelden(formular);
    });
  });
}

// ---------- Alle anderen Seiten ----------

function seiteEinrichten() {
  const email = angemeldeteEmail();
  const benutzer = BENUTZER[email];
  const rolle = benutzer.rolle;
  const startseite = STARTSEITE[rolle];

  // Kopf: Name und Rolle, Logo führt zur eigenen Startseite, Abmelden vergisst den Benutzer
  const kopf = document.querySelector(".kopf .benutzer");
  if (kopf) {
    const abmelden = kopf.querySelector("a");
    kopf.textContent = benutzer.name + " · " + ROLLEN[rolle] + " ";
    if (abmelden) {
      kopf.append(abmelden);
      abmelden.addEventListener("click", function () {
        merken(null);
      });
    }
  }

  const logo = document.querySelector(".kopf .logo");
  if (logo) {
    logo.setAttribute("href", startseite);
  }

  // Menü: nur erlaubte Seiten, leere Abschnitte ganz weglassen
  const menue = document.querySelector(".menue");
  if (menue) {
    menue.querySelectorAll("a").forEach(function (link) {
      if (!darfSeite(rolle, seiteVon(link.href))) {
        link.remove();
      }
    });

    menue.querySelectorAll(".menue-titel").forEach(function (titel) {
      const naechstes = titel.nextElementSibling;
      if (!naechstes || naechstes.classList.contains("menue-titel")) {
        titel.remove();
      }
    });
  }

  const inhalt = document.querySelector("main.inhalt");
  if (!inhalt) {
    return;
  }

  // Seite einer anderen Rolle: Inhalt durch einen Hinweis ersetzen
  if (!darfSeite(rolle, seiteVon(window.location.href))) {
    const seitenname = document.title.split(" – ")[0];
    inhalt.innerHTML =
      '<div class="seitenkopf"><div><h1>Kein Zugriff</h1></div></div>' +
      '<div class="hinweis fehler">' +
      "<p>Die Seite „" + seitenname + "“ ist für die Rolle " + ROLLEN[rolle] + " nicht freigegeben.</p>" +
      '<p><a href="' + startseite + '">Zur Startseite</a> oder als anderer Test-Benutzer ' +
      '<a href="index.html">neu anmelden</a>.</p>' +
      "</div>";
    return;
  }

  // Aktionen, die ein eigenes Recht brauchen, z. B. data-recht="buchung.korrigieren"
  inhalt.querySelectorAll("[data-recht]").forEach(function (element) {
    if (!darf(rolle, element.dataset.recht)) {
      element.remove();
    }
  });

  // Verweise auf fremde Seiten: Knöpfe ausblenden, Textlinks zu normalem Text machen
  inhalt.querySelectorAll("a[href]").forEach(function (link) {
    if (/^[a-z]+:/i.test(link.getAttribute("href")) || darfSeite(rolle, seiteVon(link.href))) {
      return;
    }

    if (link.classList.contains("btn")) {
      link.remove();
    } else {
      link.replaceWith(...link.childNodes);
    }
  });
}

// ---------- Start ----------

document.addEventListener("submit", function (ereignis) {
  const formular = ereignis.target;

  if (formular.id === "anmeldung") {
    ereignis.preventDefault();
    anmelden(formular);
  } else if (formular.method.toLowerCase() === "post") {
    ereignis.preventDefault();
    window.location.href = formular.getAttribute("action");
  }
});

if (document.getElementById("anmeldung")) {
  anmeldeseiteEinrichten();
} else {
  seiteEinrichten();
}
