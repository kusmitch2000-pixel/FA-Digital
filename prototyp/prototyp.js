// Nur für den Prototyp:
// Statische Webserver wie GitHub Pages nehmen keine Formulare mit method="post" an (Fehler 405).
// Dieses Skript fängt das Absenden ab und öffnet einfach die Zielseite aus "action".
// Sobald PHP die Formulare verarbeitet, wird die Datei nicht mehr gebraucht.
document.addEventListener("submit", function (ereignis) {
  const formular = ereignis.target;

  if (formular.method.toLowerCase() === "post") {
    ereignis.preventDefault();
    window.location.href = formular.getAttribute("action");
  }
});
