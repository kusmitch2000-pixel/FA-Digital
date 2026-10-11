<div>
    <div class="seitenkopf">
        <div>
            <h1>Auswertung</h1>
            <p class="leise">Abgeschlossene Aufträge, Zeitabweichungen und Unterbrechungen</p>
        </div>
    </div>

    <section class="karte">
        <div class="seitenkopf ohne-abstand">
            <h2>Abgeschlossene Aufträge</h2>
            <a class="btn btn-klein btn-sekundaer" href="{{ route('auswertung.export', 'auftraege') }}">CSV</a>
        </div>
        <div class="tabelle-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Auftrag</th><th>Artikel</th><th class="zahl">Gut / Ausschuss</th><th>Abschluss</th><th class="zahl">Soll-Kosten</th>
                        <th class="zahl">Ist-Kosten</th><th class="zahl">Abweichung</th><th class="zahl">€ je Gutstück Soll / Ist</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auftraege as $auftrag)
                        <tr>
                            <td><a href="{{ route('nachkalkulation', ['auftrag' => $auftrag->auftragsnummer]) }}"><strong>{{ $auftrag->auftragsnummer }}</strong></a></td>
                            <td>{{ $auftrag->artikelnummer }} {{ $auftrag->artikel }}</td>
                            <td class="zahl">{{ $auftrag->gutmenge }} / {{ $auftrag->ausschuss }}</td>
                            <td>{{ datum($auftrag->abgeschlossen_am) }}</td>
                            <td class="zahl">{{ euro($auftrag->soll_kosten) }}</td>
                            <td class="zahl">{{ euro($auftrag->ist_kosten) }}</td>
                            <td class="zahl"><span class="ampel {{ $auftrag->ampel }}"></span>{{ prozent($auftrag->abweichung_prozent) }}</td>
                            <td class="zahl">{{ euro($auftrag->soll_kosten_je_stueck) }} / {{ euro($auftrag->ist_kosten_je_gutstueck) }}</td>
                            <td><span class="badge {{ status_klasse($auftrag->status) }}">{{ status_text($auftrag->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="9">Noch kein Auftrag abgeschlossen.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="karte">
        <div class="seitenkopf ohne-abstand">
            <h2>Zeitabweichung je Arbeitsplatz</h2>
            <a class="btn btn-klein btn-sekundaer" href="{{ route('auswertung.export', 'arbeitsplaetze') }}">CSV</a>
        </div>
        <div class="tabelle-scroll">
            <table>
                <thead>
                    <tr><th>Arbeitsplatz</th><th class="zahl">Arbeitsgänge</th><th class="zahl">Soll-Zeit</th><th class="zahl">Ist-Zeit</th><th class="zahl">Abweichung</th><th>Bewertung</th></tr>
                </thead>
                <tbody>
                    @forelse ($arbeitsplaetze as $platz)
                        @php($ampel = $platz->abweichung_prozent <= 5 ? 'gruen' : ($platz->abweichung_prozent <= 10 ? 'gelb' : 'rot'))
                        <tr>
                            <td>{{ $platz->arbeitsplatz }} <span class="leise klein">{{ $platz->arbeitsplatz_nummer }}</span></td>
                            <td class="zahl">{{ $platz->arbeitsgaenge }}</td>
                            <td class="zahl">{{ minuten($platz->soll_zeit_min) }}</td>
                            <td class="zahl">{{ minuten($platz->ist_zeit_min) }}</td>
                            <td class="zahl">{{ prozent($platz->abweichung_prozent) }}</td>
                            <td><span class="ampel {{ $ampel }}"></span>{{ ['gruen' => 'im Plan', 'gelb' => 'beobachten', 'rot' => 'Soll-Zeit prüfen'][$ampel] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Noch keine Daten.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="karte">
        <div class="seitenkopf ohne-abstand">
            <h2>Wo geht Zeit verloren? Unterbrechungen nach Grund</h2>
            <a class="btn btn-klein btn-sekundaer" href="{{ route('auswertung.export', 'unterbrechungen') }}">CSV</a>
        </div>
        <div class="tabelle-scroll">
            <table>
                <thead>
                    <tr><th>Grund</th><th class="zahl">Anzahl</th><th class="zahl">Dauer</th><th>Anteil</th><th>Am häufigsten an</th></tr>
                </thead>
                <tbody>
                    @foreach ($unterbrechungen as $unterbrechung)
                        <tr>
                            <td>{{ $unterbrechung->grund }} @if ($unterbrechung->geplant)<span class="leise klein">(geplant)</span>@endif</td>
                            <td class="zahl">{{ $unterbrechung->anzahl }}</td>
                            <td class="zahl">{{ minuten($unterbrechung->dauer_min) }}</td>
                            <td>
                                @if ($unterbrechung->anteil_prozent !== null)
                                    <div class="balken"><span @class(['rot' => ! $unterbrechung->geplant]) style="width: {{ (int) $unterbrechung->anteil_prozent }}%;"></span></div>
                                    <small>{{ $unterbrechung->anteil_prozent }} %{{ $unterbrechung->geplant ? ' der Pausen' : ' der ungeplanten' }}</small>
                                @else
                                    –
                                @endif
                            </td>
                            <td>{{ $unterbrechung->haeufigster_arbeitsplatz ?? '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="leise klein">Unterbrechungen zählen nicht zur Ist-Zeit. Der Anteil gilt getrennt für ungeplante Gründe und Pausen.</p>
    </section>
</div>
