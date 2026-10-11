<div wire:poll.30s>
    <div class="seitenkopf">
        <div>
            <h1>Hallenansicht</h1>
            <p class="leise">Was gerade an jedem Arbeitsplatz passiert · Stand: {{ now()->format('d.m.Y, H:i') }} Uhr</p>
        </div>
        @if (auth()->user()->darf('auftrag.anlegen'))
            <a class="btn btn-primaer" href="{{ route('auftrag.anlegen') }}">+ Neuer Auftrag</a>
        @endif
    </div>

    @php($zustaende = $plaetze->countBy('zustand'))
    <p class="kurzinfo">
        <span><strong>Arbeitsplätze:</strong> {{ $zustaende->get('belegt', 0) }} belegt · {{ $zustaende->get('unterbrochen', 0) }} unterbrochen · {{ $zustaende->get('frei', 0) }} frei</span>
        <span><strong>Aufträge:</strong> <a href="{{ route('auftraege') }}">{{ $anzahl->get('angelegt', 0) }} angelegt · {{ $anzahl->get('freigegeben', 0) }} freigegeben · {{ $anzahl->get('in_arbeit', 0) }} in Arbeit · {{ $anzahl->get('fertig', 0) }} fertig</a></span>
    </p>

    @if ($handlungsbedarf)
        <div class="hinweis fehler">
            <p><strong>Handlungsbedarf</strong></p>
            @foreach ($handlungsbedarf as $punkt)
                <p><a href="{{ route('auftrag', $punkt['auftrag']) }}">{{ $punkt['auftrag'] }}</a>: {{ $punkt['text'] }}</p>
            @endforeach
        </div>
    @endif

    {{-- Eine Karte je Arbeitsplatz. Randfarbe = Ampel für die verbrauchte Soll-Zeit --}}
    <section class="halle">
        @foreach ($plaetze as $platz)
            <article class="platz {{ $platz->zustand === 'belegt' ? $platz->ampel : $platz->zustand }}">
                <div class="platz-kopf">
                    <span class="platz-name">{{ $platz->arbeitsplatz }}</span>
                    <span class="platz-nr">{{ $platz->arbeitsplatz_nummer }}</span>
                </div>
                @if ($platz->zustand === 'frei')
                    @php($naechster = $naechste->get($platz->arbeitsplatz_id))
                    <p><span class="badge frei">frei</span></p>
                    <p class="leise klein">
                        @if ($naechster)
                            Als Nächstes: <a href="{{ route('auftrag', $naechster->auftragsnummer) }}">{{ $naechster->auftragsnummer }}</a> · AG {{ $naechster->ag_nummer }} {{ $naechster->arbeitsgang }}{{ $naechster->bereitschaft === 'wartet' ? ', wartet auf AG '.$naechster->wartet_auf_ag : ', bereit' }}
                        @else
                            Kein Arbeitsgang im Vorrat.
                        @endif
                    </p>
                @else
                    <p>
                        <span class="badge {{ $platz->zustand }}">
                            {{ $platz->zustand === 'unterbrochen' ? 'unterbrochen · '.$platz->unterbrechungsgrund : 'belegt · '.status_text($platz->laufende_taetigkeit) }}
                        </span>
                    </p>
                    <p class="platz-auftrag"><a href="{{ route('auftrag', $platz->auftragsnummer) }}">{{ $platz->auftragsnummer }}</a> · AG {{ $platz->ag_nummer }} {{ $platz->arbeitsgang }}</p>
                    <p class="leise klein">
                        {{ $platz->artikel }} · {{ $platz->menge }} Stück<br>
                        {{ $platz->werker }} ·
                        @if ($platz->zustand === 'unterbrochen')
                            steht seit {{ uhrzeit($platz->unterbrochen_seit) }} ({{ $platz->unterbrochen_min }} min)
                        @else
                            seit {{ uhrzeit($platz->laeuft_seit) }}
                        @endif
                    </p>
                    <div class="balken"><span class="{{ $platz->ampel }}" style="width: {{ min(100, (int) $platz->verbraucht_prozent) }}%;"></span></div>
                    <p class="klein">
                        <span class="ampel {{ $platz->ampel }}"></span><span @class(['ueber' => $platz->ampel === 'rot'])>{{ zahl($platz->ist_zeit_min) }} von {{ minuten($platz->soll_zeit_min) }} Soll ({{ $platz->verbraucht_prozent }} %)</span>
                    </p>
                @endif
            </article>
        @endforeach
    </section>

    <p class="legende">
        <span><span class="ampel gruen"></span>bis 80 % der Soll-Zeit verbraucht</span>
        <span><span class="ampel gelb"></span>80 bis 100 %</span>
        <span><span class="ampel rot"></span>Soll-Zeit überschritten</span>
    </p>

    {{-- Meister-Ansicht: nur die Aufträge, die gerade in der Werkstatt sind --}}
    <section class="karte abstand-oben">
        <h2>Aufträge in der Werkstatt</h2>
        <div class="tabelle-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Auftrag</th>
                        <th>Liefertermin</th>
                        <th>Stand</th>
                        <th class="zahl">Zeit verbraucht</th>
                        <th>Checkliste</th>
                        <th>Zertifikate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auftraege as $auftrag)
                        <tr>
                            <td class="kein-umbruch">
                                <a href="{{ route('auftrag', $auftrag->auftragsnummer) }}"><strong>{{ $auftrag->auftragsnummer }}</strong></a>
                                <br><span class="leise klein">{{ $auftrag->artikel }} · {{ $auftrag->menge }} Stück</span>
                            </td>
                            <td class="kein-umbruch">{{ datum($auftrag->liefertermin) }} @if ($auftrag->verzug)<span class="badge verzug">Verzug</span>@endif</td>
                            <td>
                                <span class="badge {{ status_klasse($auftrag->status) }}">{{ status_text($auftrag->status) }}</span>
                                <span class="klein">{{ $auftrag->fortschritt }} AG{{ $auftrag->aktueller_arbeitsgang ? ' · jetzt '.$auftrag->aktueller_arbeitsgang : '' }}</span>
                            </td>
                            <td class="zahl">{{ $auftrag->zeit_verbraucht_prozent ?? 0 }} %</td>
                            <td class="klein">
                                @if ($auftrag->pruefpunkte_nicht_in_ordnung > 0)
                                    <span class="badge verzug">{{ $auftrag->pruefpunkte_nicht_in_ordnung }} n. i. O.</span>
                                @endif
                                {{ $auftrag->pruefpunkte_offen > 0 ? $auftrag->pruefpunkte_offen.' offen' : 'alles geprüft' }}
                            </td>
                            <td class="klein">{{ $auftrag->zertifikate ?? '–' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Gerade ist kein Auftrag in der Werkstatt.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="karte">
        <h2>Letzte Buchungen</h2>
        <div class="tabelle-scroll">
            <table>
                <thead>
                    <tr><th class="zahl">Zeit</th><th>Mitarbeiter</th><th>Arbeitsplatz</th><th>Auftrag / Arbeitsgang</th><th>Buchung</th></tr>
                </thead>
                <tbody>
                    @forelse ($buchungen as $buchung)
                        <tr>
                            <td class="zahl">{{ zeitpunkt($buchung->zeitpunkt, 'd.m. H:i') }}</td>
                            <td>{{ $buchung->mitarbeiter }}</td>
                            <td>{{ $buchung->arbeitsplatz }}</td>
                            <td><a href="{{ route('auftrag', $buchung->auftragsnummer) }}">{{ $buchung->auftragsnummer }}</a> · AG {{ $buchung->ag_nummer }}</td>
                            <td>
                                @if (str_starts_with($buchung->buchung, 'Unterbrochen'))
                                    <span class="badge unterbrochen">{{ $buchung->buchung }}</span>
                                @else
                                    {{ $buchung->buchung }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Noch keine Buchungen.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
