<div>
    <div class="seitenkopf">
        <div>
            <h1>Aufträge</h1>
            <p class="leise">Fertigungsaufträge und Fortschritt. Nachkalkulierte Aufträge stehen in der Auswertung.</p>
        </div>
        @if (auth()->user()->darf('auftrag.anlegen'))
            <a class="btn btn-primaer" href="{{ route('auftrag.anlegen') }}">+ Neuer Auftrag</a>
        @endif
    </div>

    {{-- Links die Suche nach einem bestimmten Teil, rechts der Status als Knöpfe --}}
    <div class="karte filter-zeile">
        <div class="feld">
            <label for="suche">Suche</label>
            <input id="suche" type="search" wire:model.live.debounce.300ms="suche" placeholder="Auftrag, Artikel oder Kunde">
        </div>
        <div class="feld">
            <span class="feld-titel">Status</span>
            <div class="knopfleiste status-knoepfe" role="group" aria-label="Status">
                @foreach (['' => ['Alle', $gesamt], 'offen' => ['Offen', $offen]] + collect(['angelegt', 'freigegeben', 'in_arbeit', 'messen', 'fertig', 'storniert'])
                    ->mapWithKeys(fn ($wert) => [$wert => [status_text($wert), $anzahl->get($wert, 0)]])->all() as $wert => [$name, $zahl])
                    <button type="button" wire:click="$set('status', '{{ $wert }}')" aria-pressed="{{ $status === $wert ? 'true' : 'false' }}"
                        @class(['btn', 'btn-klein', 'btn-primaer' => $status === $wert, 'btn-sekundaer' => $status !== $wert])>{{ $name }} <span class="anzahl">{{ $zahl }}</span></button>
                @endforeach
            </div>
        </div>
    </div>
    <p class="knopfleiste" role="status">
        <span class="leise klein">{{ $gefiltert ? $auftraege->count().' von '.$gesamt.' Aufträgen' : 'Alle '.$gesamt.' Aufträge' }}</span>
        @if ($gefiltert)
            <button type="button" wire:click="zuruecksetzen" class="btn btn-klein btn-sekundaer">Filter zurücksetzen</button>
        @endif
    </p>

    <section class="karte">
        <div class="tabelle-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Auftrag</th><th>Artikel</th><th>Kunde</th><th class="zahl">Menge</th><th>Liefertermin</th><th>Status</th>
                        <th>Aktueller Prozess</th><th class="zahl">Soll-Zeit</th><th class="zahl">Ist-Zeit</th><th>Zertifikate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auftraege as $auftrag)
                        <tr wire:key="auftrag-{{ $auftrag->fertigungsauftrag_id }}">
                            <td><a href="{{ route('auftrag', $auftrag->auftragsnummer) }}"><strong>{{ $auftrag->auftragsnummer }}</strong></a></td>
                            <td>{{ $auftrag->artikelnummer }} {{ $auftrag->artikel }}</td>
                            <td>{{ $auftrag->kunde ?? '–' }}</td>
                            <td class="zahl">{{ $auftrag->menge }}</td>
                            <td>{{ datum($auftrag->liefertermin) }} @if ($auftrag->verzug)<span class="badge verzug">Verzug</span>@endif</td>
                            <td><span class="badge {{ status_klasse($auftrag->status) }}">{{ status_text($auftrag->status) }}</span></td>
                            <td class="prozess">
                                @php($ag = $aktuell->get($auftrag->fertigungsauftrag_id))
                                @if ($auftrag->status === 'storniert')
                                    <span class="leise klein">storniert, kein Prozess</span>
                                @else
                                    @if ($ag)
                                        <strong>{{ $ag->arbeitsplatz }}</strong>
                                        @if ($auftrag->status === 'angelegt')
                                            <span class="badge offen">noch nicht freigegeben</span>
                                        @elseif ($ag->status === 'offen')
                                            <span class="badge erledigt">bereit</span>
                                        @else
                                            <span class="badge {{ status_klasse($ag->status) }}">{{ status_text($ag->status) }}</span>
                                        @endif
                                        <br><small>AG {{ $ag->ag_nummer }} {{ $ag->bezeichnung }}</small>
                                    @else
                                        <strong>abgeschlossen</strong> <span class="badge erledigt">alle erledigt</span>
                                    @endif
                                    <div class="balken"><span @class(['voll' => $auftrag->ag_erledigt == $auftrag->ag_gesamt]) style="width: {{ $auftrag->ag_gesamt ? round(100 * $auftrag->ag_erledigt / $auftrag->ag_gesamt) : 0 }}%;"></span></div>
                                    <small class="leise">{{ $auftrag->ag_erledigt }} von {{ $auftrag->ag_gesamt }} AG erledigt</small>
                                @endif
                            </td>
                            <td class="zahl">{{ minuten($auftrag->soll_zeit_min) }}</td>
                            <td class="zahl">{{ minuten($auftrag->ist_zeit_min) }}</td>
                            <td>{{ $auftrag->zertifikate ?? '–' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10">Kein Auftrag passt zum Filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
