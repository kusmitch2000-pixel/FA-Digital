<div wire:poll.30s>
    <div class="seitenkopf">
        <div>
            <h1>Startseite</h1>
            <p class="leise">Aufgaben für den Meister · Stand: {{ now()->format('d.m.Y, H:i') }} Uhr</p>
        </div>
    </div>

    <section class="karte">
        <h2>Mein Arbeitsaufkommen</h2>
        <p>Hier sehen Sie überfällige offene Aufträge und Zertifikate, die zur Freigabe bereit sind.</p>
        <p class="kurzinfo">
            <span><strong>{{ $ueberfaelligeAuftraege->count() }}</strong> überfällige Aufträge</span>
            <span><strong>{{ $freigabebereiteZertifikate->count() }}</strong> Zertifikate zur Freigabe</span>
        </p>

        <h3>Überfällige Aufträge</h3>
        <div class="tabelle-scroll">
            <table>
                <thead><tr><th>Auftrag</th><th>Artikel</th><th>Liefertermin</th><th>Status</th><th>Nächster Arbeitsgang</th></tr></thead>
                <tbody>
                    @forelse ($ueberfaelligeAuftraege as $auftrag)
                        <tr wire:key="auftrag-{{ $auftrag->fertigungsauftrag_id }}">
                            <td><a href="{{ route('auftrag', $auftrag->auftragsnummer) }}">{{ $auftrag->auftragsnummer }}</a></td>
                            <td>{{ $auftrag->artikelnummer }} {{ $auftrag->artikel }}</td>
                            <td>{{ datum($auftrag->liefertermin) }}</td>
                            <td>{{ status_text($auftrag->status) }}</td>
                            <td>{{ $auftrag->aktueller_arbeitsgang ?? '–' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Keine überfälligen offenen Aufträge.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h3>Zertifikate zur Freigabe</h3>
        <div class="tabelle-scroll">
            <table>
                <thead><tr><th>Auftrag</th><th>Artikel</th><th>Zertifikat</th></tr></thead>
                <tbody>
                    @forelse ($freigabebereiteZertifikate as $zertifikat)
                        <tr wire:key="zertifikat-{{ $zertifikat->auftrag_zertifikat_id }}">
                            <td>{{ $zertifikat->auftragsnummer }}</td>
                            <td>{{ $zertifikat->artikelnummer }} {{ $zertifikat->artikel }}</td>
                            <td>{{ $zertifikat->kuerzel }} {{ $zertifikat->zertifikat }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">Keine Zertifikate zur Freigabe bereit.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p><a class="btn btn-primaer" href="{{ route('zertifikate') }}">Zertifikatsfreigaben öffnen</a></p>
    </section>
</div>
