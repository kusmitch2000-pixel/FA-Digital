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

    <div class="karte formular-raster">
        <div class="feld">
            <label for="suche">Suche</label>
            <input id="suche" type="search" wire:model.live.debounce.300ms="suche" placeholder="Auftrag, Artikel oder Kunde">
        </div>
        <div class="feld">
            <label for="status">Status</label>
            <select id="status" wire:model.live="status">
                <option value="">alle</option>
                @foreach (['angelegt', 'freigegeben', 'in_arbeit', 'fertig', 'storniert'] as $wert)
                    <option value="{{ $wert }}">{{ status_text($wert) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <section class="karte">
        <div class="tabelle-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Auftrag</th><th>Artikel</th><th>Kunde</th><th class="zahl">Menge</th><th>Liefertermin</th><th>Status</th>
                        <th>Fortschritt</th><th class="zahl">Soll-Zeit</th><th class="zahl">Ist-Zeit</th><th>Zertifikate</th>
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
                            <td>
                                <div class="balken"><span style="width: {{ $auftrag->ag_gesamt ? round(100 * $auftrag->ag_erledigt / $auftrag->ag_gesamt) : 0 }}%;"></span></div>
                                <small>{{ $auftrag->ag_erledigt }} von {{ $auftrag->ag_gesamt }} AG{{ $auftrag->aktueller_arbeitsgang && $auftrag->status !== 'storniert' ? ' · '.$auftrag->aktueller_arbeitsgang : '' }}</small>
                            </td>
                            <td class="zahl">{{ minuten($auftrag->soll_zeit_min) }}</td>
                            <td class="zahl">{{ minuten($auftrag->ist_zeit_min) }}</td>
                            <td>{{ $auftrag->zertifikate ?? '–' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10">Keine Aufträge gefunden.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
