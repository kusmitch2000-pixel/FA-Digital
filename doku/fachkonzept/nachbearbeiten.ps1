# Oeffnet das gespeicherte Fachkonzept, setzt Seitenumbrueche, aktualisiert das
# Inhaltsverzeichnis, speichert und exportiert ein PDF.
# Hintergrund: Word haengt auf diesem Rechner beim ersten SaveAs2 eines neu erzeugten
# Dokuments. Eine bereits gespeicherte Datei laesst sich per Skript bearbeiten und speichern.

param(
  [string]$Ordner = $PSScriptRoot,
  [string]$Protokoll = "",
  [switch]$Sichtbar,
  [switch]$NurPdf,
  [switch]$OhnePdf
)

function Log([string]$m) { if ($Protokoll) { Add-Content -Path $Protokoll -Value ((Get-Date -Format "HH:mm:ss") + " " + $m) } }

$ErrorActionPreference = "Stop"
$doku = Split-Path $Ordner -Parent
$docxPfad = Join-Path $doku "Fachkonzept_FA-Digital.docx"
$pdfPfad = Join-Path $doku "Fachkonzept_FA-Digital.pdf"

# Ueberschriften und ob sie auf einer neuen Seite beginnen
$umbrueche = @{
  "Ueberblick" = $true
  "Prozesse" = $false
  "Arbeitsplaene und Ablaeufe verbessern" = $false
  "Anwendungsfaelle" = $true
  "Nicht-funktionale Anforderungen" = $true
}

function Ohne-Umlaute([string]$t) {
  return $t.Replace([string][char]0x00FC, "ue").Replace([string][char]0x00E4, "ae").Replace([string][char]0x00F6, "oe").Replace([string][char]0x00DC, "Ue")
}

$word = New-Object -ComObject Word.Application
$word.Visible = [bool]$Sichtbar
$word.DisplayAlerts = 0
try {
  $doc = $word.Documents.Open($docxPfad)
  Log "geoeffnet"
  if (-not $NurPdf) {
  foreach ($p in $doc.Paragraphs) {
    $stil = $p.Range.ParagraphFormat.OutlineLevel
    if ($stil -le 2) {
      $text = Ohne-Umlaute ($p.Range.Text.Trim())
      if ($umbrueche.ContainsKey($text)) {
        $p.Range.ParagraphFormat.PageBreakBefore = $(if ($umbrueche[$text]) { -1 } else { 0 })
        Log ("Umbruch " + $text + " = " + $umbrueche[$text])
      }
    }
  }
  # Spiegelstriche statt Punkte wie in der Vorlage
  $liste = $doc.Styles.Item(-49)
  $vorlage = $liste.ListTemplate
  $ebene = $vorlage.ListLevels.Item(1)
  $ebene.NumberFormat = "-"
  $ebene.Font.Name = "Verdana"
  $ebene.NumberPosition = $word.CentimetersToPoints(0.75)
  $ebene.TextPosition = $word.CentimetersToPoints(1.25)
  $ebene.TabPosition = $word.CentimetersToPoints(1.25)
  $liste.LinkToListTemplate($vorlage, 1)
  Log "Spiegelstriche gesetzt"
  $doc.TablesOfContents.Item(1).Update()
  Log "Verzeichnis aktualisiert"
  $doc.Save()
  Log "docx gespeichert"
  }
  if (-not $OhnePdf) {
    $doc.ExportAsFixedFormat($pdfPfad, 17)
    Log "pdf exportiert"
  }
  "Seiten: " + $doc.ComputeStatistics(2)
  $doc.Close(0)
}
finally {
  $word.Quit()
}
Log "Ende"
