<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Werkszeugnis {{ $zertifikat->zertifikatsart->kuerzel }} {{ $zertifikat->auftrag->auftragsnummer }} – FA-Digital</title>
    <link rel="stylesheet" href="{{ asset('css/production.css') }}">
</head>
<body class="druckseite">
<main class="dokument">
    <div class="knopfleiste nicht-drucken">
        <button type="button" class="btn btn-primaer" onclick="window.print()">Drucken oder als PDF speichern</button>
        <a class="btn btn-sekundaer" href="{{ route('zertifikate') }}">Zurück</a>
    </div>

    @if ($zertifikat->status !== 'freigegeben')
        <div class="hinweis warnung">
            <strong>Entwurf</strong>: noch nicht freigegeben.
            @if ($stand->offen > 0) {{ $stand->offen }} Prüfpunkt(e) offen. @endif
            @if ($stand->nicht_in_ordnung > 0) {{ $stand->nicht_in_ordnung }} Prüfpunkt(e) nicht in Ordnung. @endif
        </div>
    @endif

    <h1>Werkszeugnis {{ $zertifikat->zertifikatsart->kuerzel }}</h1>
    <p class="leise">{{ $zertifikat->zertifikatsart->bezeichnung }} nach DIN EN 10204. {{ $zertifikat->zertifikatsart->beschreibung }}</p>

    <dl class="kopfdaten">
        <dt>Auftrag</dt><dd>{{ $zertifikat->auftrag->auftragsnummer }}</dd>
        <dt>Kunde</dt><dd>{{ $zertifikat->auftrag->kunde ?? '–' }}</dd>
        <dt>Artikel</dt><dd>{{ $zertifikat->auftrag->artikel->artikelnummer }} {{ $zertifikat->auftrag->artikel->bezeichnung }}</dd>
        <dt>Menge</dt><dd>{{ $zertifikat->auftrag->menge }} Stück</dd>
        <dt>Fertig gemeldet</dt><dd>{{ zeitpunkt($zertifikat->auftrag->abgeschlossen_am) }}</dd>
    </dl>

    <h2>Prüfergebnisse</h2>
    <table>
        <thead>
            <tr><th>AG</th><th>Prüfpunkt</th><th>Soll</th><th>Toleranz</th><th>Ist</th><th>Ergebnis</th><th>Geprüft</th></tr>
        </thead>
        <tbody>
            @foreach ($pruefpunkte as $punkt)
                <tr>
                    <td>{{ $punkt->ag_nummer }} {{ $punkt->arbeitsgang }}</td>
                    <td>{{ $punkt->pruefpunkt }}</td>
                    <td>{{ $punkt->art === 'messwert' ? zahl($punkt->sollwert, 3).' '.$punkt->einheit : 'Sichtprüfung' }}</td>
                    <td>{{ $punkt->art === 'messwert' ? zahl($punkt->untere_grenze, 3).' bis '.zahl($punkt->obere_grenze, 3) : '–' }}</td>
                    <td>{{ $punkt->istwert !== null ? zahl($punkt->istwert, 3).' '.$punkt->einheit : '–' }}</td>
                    <td>{{ status_text($punkt->ergebnis) }}</td>
                    <td>{{ $punkt->geprueft_von ?? '–' }}{{ $punkt->geprueft_am ? ', '.zeitpunkt($punkt->geprueft_am) : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="unterschrift">
        @if ($zertifikat->status === 'freigegeben')
            Freigegeben am {{ zeitpunkt($zertifikat->freigegeben_am) }} von {{ $zertifikat->freigegebenVon->name }} · Datei {{ $zertifikat->dateiname }}
        @else
            Freigabe: ______________________ (Meister)
        @endif
    </p>
</main>
</body>
</html>
