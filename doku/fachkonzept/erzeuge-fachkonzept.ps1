# Erzeugt Fachkonzept_FA-Digital.docx und .pdf mit Microsoft Word aus inhalt.json.
# Aufbau wie die Vorlage "Fachkonzept Meldung Arbeitsort" (Dr. Heimann):
# Titel, Version/Stand/Autoren, Inhalt, Ueberblick, Prozesse, Anwendungsfaelle.
# Aufruf: powershell -File erzeuge-fachkonzept.ps1 -Sichtbar
#
# Bekanntes Problem auf Daniels Rechner (27.09.2026): Word haengt beim ersten Speichern
# eines per Skript neu erzeugten Dokuments (SaveAs2) und beim PDF-Export, ohne einen
# Dialog zu zeigen. Umweg: mit -Sichtbar starten, sobald das Dokument fertig aufgebaut
# ist in Word mit F12 als Fachkonzept_FA-Digital.docx speichern, danach
# nachbearbeiten.ps1 -OhnePdf ausfuehren und das PDF in Word ueber F12 > Dateityp PDF erzeugen.
# (Die Datei enthaelt bewusst keine Umlaute, weil PowerShell 5.1 Skripte ohne BOM als ANSI liest.)

param(
  [string]$Ordner = $PSScriptRoot,
  [string]$Protokoll = "",
  [switch]$Sichtbar
)

function Log([string]$m) { if ($Protokoll) { Add-Content -Path $Protokoll -Value ((Get-Date -Format "HH:mm:ss") + " " + $m) } }

$ErrorActionPreference = "Stop"
$doku = Split-Path $Ordner -Parent
$jsonPfad = Join-Path $Ordner "inhalt.json"
$bildOrdner = Join-Path $doku "prozesse"
$docxPfad = Join-Path $doku "Fachkonzept_FA-Digital.docx"
$pdfPfad = Join-Path $doku "Fachkonzept_FA-Digital.pdf"

$bloecke = Get-Content -Raw -Encoding UTF8 $jsonPfad | ConvertFrom-Json

# Word-Konstanten
$STANDARD = -1; $UEBERSCHRIFT1 = -2; $UEBERSCHRIFT2 = -3; $TITEL = -63; $UNTERTITEL = -75
$AUFZAEHLUNG = -49; $VERZEICHNIS1 = -20; $VERZEICHNIS2 = -21
$FARBE_BLAU = 0 + 48 * 256 + 93 * 65536   # #00305d als BGR-Wert

Log "Start"
$word = New-Object -ComObject Word.Application
Log "Word gestartet"
$word.Visible = [bool]$Sichtbar
$word.DisplayAlerts = 0

try {
  $doc = $word.Documents.Add()
  Log "Dokument angelegt"
  $doc.Content.LanguageID = 1031

  $seite = $doc.PageSetup
  $seite.PaperSize = 7
  foreach ($rand in "TopMargin", "BottomMargin", "LeftMargin", "RightMargin") {
    $seite.$rand = $word.CentimetersToPoints(2.5)
  }

  function Formatiere($id, $schrift, $groesse, $blau, $vorher, $nachher) {
    $s = $doc.Styles.Item($id)
    $s.Font.Name = $schrift
    $s.Font.Size = $groesse
    $s.Font.Bold = $false
    if ($blau) { $s.Font.Color = $FARBE_BLAU }
    $s.ParagraphFormat.SpaceBefore = $vorher
    $s.ParagraphFormat.SpaceAfter = $nachher
  }
  Formatiere $STANDARD "Verdana" 10 $false 0 6
  $doc.Styles.Item($STANDARD).ParagraphFormat.LineSpacingRule = 5
  $doc.Styles.Item($STANDARD).ParagraphFormat.LineSpacing = $word.LinesToPoints(1.15)
  Formatiere $TITEL "Calibri Light" 26 $true 0 4
  Formatiere $UNTERTITEL "Verdana" 11 $true 0 18
  Formatiere $UEBERSCHRIFT1 "Calibri Light" 16 $true 18 8
  Formatiere $UEBERSCHRIFT2 "Calibri Light" 13 $true 14 6
  Formatiere $AUFZAEHLUNG "Verdana" 10 $false 0 2
  Formatiere $VERZEICHNIS1 "Verdana" 10 $false 4 2
  Formatiere $VERZEICHNIS2 "Verdana" 10 $false 0 2

  # Schreibt einen Absatz ans Dokumentende und gibt seinen Bereich zurueck
  function Absatz([string]$text, $stil) {
    $r = $doc.Paragraphs.Last.Range
    $r.Style = $stil
    $r.Font.Reset()
    $r.InsertBefore($text)
    $ergebnis = $doc.Range($r.Start, $r.Start + $text.Length)
    $r.InsertParagraphAfter()
    return $ergebnis
  }

  Log "Formate gesetzt"
  $nr = 0
  foreach ($b in $bloecke) {
    $nr++
    Log ("Block " + $nr + " " + $b.typ)
    switch ($b.typ) {
      "titel"      { [void](Absatz $b.text $TITEL) }
      "untertitel" { [void](Absatz $b.text $UNTERTITEL) }
      "eng" {
        $text = $b.text + [string]$b.markiert
        $r = Absatz $text $STANDARD
        $r.ParagraphFormat.SpaceAfter = 0
        if ($b.markiert) {
          $m = $doc.Range($r.Start + $b.text.Length, $r.End)
          $m.HighlightColorIndex = 7
        }
      }
      "inhalt" {
        $r = Absatz "Inhalt" $STANDARD
        $r.ParagraphFormat.SpaceBefore = 24
        $r.Font.Name = "Calibri Light"; $r.Font.Size = 16; $r.Font.Color = $FARBE_BLAU
        [void](Absatz "[[VERZEICHNIS]]" $STANDARD)
      }
      "h1" {
        $r = Absatz $b.text $UEBERSCHRIFT1
        if ($b.neueSeite) { $r.ParagraphFormat.PageBreakBefore = -1 }
      }
      "h2" {
        $r = Absatz $b.text $UEBERSCHRIFT2
        $r.ParagraphFormat.KeepWithNext = -1
        if ($b.neueSeite) { $r.ParagraphFormat.PageBreakBefore = -1 }
      }
      "p" {
        $r = Absatz $b.text $STANDARD
        $r.ParagraphFormat.KeepWithNext = -1
      }
      "li" {
        $text = [string]$b.fett + $b.text
        $r = Absatz $text $AUFZAEHLUNG
        if ($b.fett) { $doc.Range($r.Start, $r.Start + $b.fett.Length).Bold = $true }
      }
      "bild" {
        $r = $doc.Paragraphs.Last.Range
        $r.Style = $STANDARD
        $bild = $doc.InlineShapes.AddPicture((Join-Path $bildOrdner $b.datei), $false, $true, $r)
        $bild.LockAspectRatio = -1
        $bild.Width = $word.CentimetersToPoints(16)
        $doc.Paragraphs.Last.Range.InsertParagraphAfter()
      }
    }
  }

  Log "Bloecke fertig"
  # Der letzte, leere Absatz soll kein Spiegelstrich sein
  $doc.Paragraphs.Last.Range.Style = $STANDARD

  # Inhaltsverzeichnis an die Stelle des Platzhalters setzen
  $platz = $doc.Content
  $gefunden = $platz.Find.Execute("[[VERZEICHNIS]]")
  if (-not $gefunden) { throw "Platzhalter fuer das Verzeichnis nicht gefunden" }
  $platz.Text = ""
  $verzeichnis = $doc.TablesOfContents.Add($platz, $true, 1, 2)
  Log "Verzeichnis eingefuegt"

  # Fusszeile rechts: "Fachkonzept FA-Digital  <Seite>"
  $fuss = $doc.Sections.Item(1).Footers.Item(1)
  $fuss.Range.Text = "Fachkonzept FA-Digital  "
  $fuss.Range.Font.Name = "Verdana"
  $fuss.Range.Font.Size = 8
  $fuss.Range.ParagraphFormat.Alignment = 2
  $ende = $fuss.Range
  [void]$ende.MoveEnd(1, -1)
  $ende.Collapse(0)
  [void]$fuss.Range.Fields.Add($ende, 33)

  Log "Fusszeile fertig"
  $verzeichnis.Update()
  Log "Verzeichnis aktualisiert"

  $doc.SaveAs2($docxPfad, 16)
  Log "docx gespeichert"
  $doc.SaveAs2($pdfPfad, 17)
  Log "pdf gespeichert"
  "Seiten: " + $doc.ComputeStatistics(2)
  $doc.Close(0)
}
finally {
  $word.Quit()
  [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($word)
}
"Fertig: $docxPfad"
