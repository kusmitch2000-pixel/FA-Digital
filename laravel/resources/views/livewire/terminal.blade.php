<div wire:poll.30s>
    <div class="seitenkopf">
        <div>
            <h1>Werker-Terminal</h1>
            <p class="leise">{{ $benutzer->name }} · Personalnummer {{ $benutzer->personalnummer }}</p>
        </div>
        <div class="knopfleiste">
            <label for="arbeitsplatz" class="leise ohne-abstand">Arbeitsplatz</label>
            <select id="arbeitsplatz" wire:model.live="arbeitsplatz" class="schmal">
                @foreach ($plaetze as $platz)
                    <option value="{{ $platz->nummer }}">{{ $platz->bezeichnung }} ({{ $platz->nummer }})</option>
                @endforeach
            </select>
        </div>
    </div>

    @error('aktion')<div class="hinweis fehler" role="alert">{{ $message }}</div>@enderror

    @if ($aktuell)
        @php($ampel = $zeit->ist_zeit_min <= 0.8 * $zeit->soll_zeit_min ? 'gruen' : ($zeit->ist_zeit_min <= $zeit->soll_zeit_min ? 'gelb' : 'rot'))
        @php($prozent = $zeit->soll_zeit_min > 0 ? round(100 * $zeit->ist_zeit_min / $zeit->soll_zeit_min) : 0)
        @php($offen = $aktuell->pruefpunkte->where('ergebnis', 'offen')->count())

        {{-- Laufender Arbeitsgang mit großen Knöpfen für die Bedienung an der Maschine --}}
        <section class="karte terminal-aktiv">
            <div class="spalten-gleich">
                <div>
                    <p class="leise klein ohne-abstand">{{ $unterbrechung ? 'Unterbrochener' : 'Laufender' }} Arbeitsgang · {{ $aktuell->arbeitsplatz->bezeichnung }}</p>
                    <p class="terminal-titel">{{ $aktuell->auftrag->auftragsnummer }} · AG {{ $aktuell->ag_nummer }} {{ $aktuell->bezeichnung }}</p>
                    <p>
                        {{ $aktuell->auftrag->artikel->artikelnummer }} {{ $aktuell->auftrag->artikel->bezeichnung }} · {{ $aktuell->auftrag->menge }} Stück<br>
                        @if ($unterbrechung)
                            <span class="badge unterbrochen">Unterbrochen seit {{ uhrzeit($unterbrechung->beginn) }}: {{ $unterbrechung->grund->bezeichnung }}</span>
                        @elseif ($buchung)
                            <span class="badge laeuft">{{ status_text($buchung->taetigkeit) }} seit {{ uhrzeit($buchung->beginn) }}</span>
                        @endif
                    </p>
                    <p class="leise klein ohne-abstand">Zeit bisher (Rüsten {{ zahl($zeit->ist_ruesten_min) }} min + Bearbeiten {{ zahl($zeit->ist_bearbeiten_min) }} min)</p>
                    <p @class(['uhr', 'ueber' => $ampel === 'rot'])>{{ zahl($zeit->ist_zeit_min) }} <span class="leise uhr-zusatz">von {{ minuten($zeit->soll_zeit_min) }} Soll</span></p>
                    <div class="balken gross"><span class="{{ $ampel }}" style="width: {{ min(100, $prozent) }}%;"></span></div>
                    @if ($ampel === 'rot')
                        <p class="klein ueber">Soll-Zeit um {{ minuten($zeit->ist_zeit_min - $zeit->soll_zeit_min) }} überschritten</p>
                    @else
                        <p class="klein leise">{{ $prozent }} % der Soll-Zeit verbraucht</p>
                    @endif
                </div>

                <div>
                    @if ($unterbrechung)
                        <p>Unterbrechungen zählen nicht zur Ist-Zeit. Sie werden getrennt nach Grund ausgewertet.</p>
                        <button wire:click="fortsetzen" wire:loading.attr="disabled" class="btn btn-erfolg btn-gross btn-breit">{{ status_text($aktuell->zeitbuchungen->where('gueltig', true)->last()?->taetigkeit ?? 'bearbeiten') }} fortsetzen</button>
                    @else
                        <div class="knopfleiste">
                            <button wire:click="wechseln('ruesten')" wire:loading.attr="disabled" @class(['btn', 'btn-primaer' => $buchung?->taetigkeit === 'ruesten', 'btn-sekundaer' => $buchung?->taetigkeit !== 'ruesten'])>Rüsten</button>
                            <button wire:click="wechseln('bearbeiten')" wire:loading.attr="disabled" @class(['btn', 'btn-primaer' => $buchung?->taetigkeit === 'bearbeiten', 'btn-sekundaer' => $buchung?->taetigkeit !== 'bearbeiten'])>Bearbeiten</button>
                        </div>
                        {{-- Fertigmeldung mit Gutmenge und Ausschuss --}}
                        <form wire:submit="fertigMelden">
                            <div class="formular-raster">
                                <div class="feld">
                                    <label for="gutmenge">Gutmenge</label>
                                    <input type="number" id="gutmenge" min="0" wire:model="gutmenge">
                                </div>
                                <div class="feld">
                                    <label for="ausschuss">Ausschuss</label>
                                    <input type="number" id="ausschuss" min="0" wire:model="ausschuss">
                                </div>
                            </div>
                            @error('gutmenge')<small class="ueber">{{ $message }}</small>@enderror
                            <div class="feld">
                                <label for="bemerkung">Bemerkung</label>
                                <input type="text" id="bemerkung" wire:model="bemerkung" placeholder="optional" maxlength="255">
                            </div>
                            @if ($offen > 0)
                                <p class="klein ueber">Erst die Checkliste abarbeiten: {{ $offen }} {{ $offen === 1 ? 'Prüfpunkt ist' : 'Prüfpunkte sind' }} noch offen.</p>
                            @endif
                            <button type="submit" class="btn btn-erfolg btn-gross btn-breit" wire:loading.attr="disabled" @disabled($offen > 0)>Arbeitsgang fertig melden</button>
                        </form>
                    @endif
                </div>
            </div>

            @unless ($unterbrechung)
                <hr class="trenner">
                {{-- Unterbrechen nur mit Grund. Die Unterbrechungszeit zählt nicht zur Ist-Zeit. --}}
                <form wire:submit="unterbrechen">
                    <fieldset>
                        <legend>Unterbrechen: Warum geht es gerade nicht weiter?</legend>
                        <div class="gruende">
                            @foreach ($gruende as $g)
                                <label class="grund"><input type="radio" wire:model="grund" value="{{ $g->id }}"> {{ $g->bezeichnung }}</label>
                            @endforeach
                        </div>
                    </fieldset>
                    @error('grund')<small class="ueber">{{ $message }}</small>@enderror
                    <div class="knopfleiste">
                        <input type="text" wire:model="unterbrechungBemerkung" placeholder="Bemerkung (optional)" maxlength="255" class="schmal">
                        <button type="submit" class="btn btn-sekundaer btn-gross" wire:loading.attr="disabled">Unterbrechen</button>
                    </div>
                </form>
            @endunless
        </section>

        {{-- Checkliste: liefert die Daten für das Werkszertifikat (Anwendungsfall 4) --}}
        @if ($aktuell->pruefpunkte->isNotEmpty())
            <section class="karte">
                <h2>Checkliste AG {{ $aktuell->ag_nummer }} {{ $aktuell->bezeichnung }}</h2>
                <p class="leise klein">Messwerte werden automatisch mit der Toleranz verglichen. Nach Nacharbeit einfach neu messen.</p>
                <div class="tabelle-scroll">
                    <table>
                        <thead>
                            <tr><th>Prüfpunkt</th><th>Soll / Toleranz</th><th>Ist</th><th>Bemerkung</th><th>Ergebnis</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($aktuell->pruefpunkte as $punkt)
                                <tr wire:key="pruefpunkt-{{ $punkt->id }}" @class(['hervorgehoben' => $punkt->ergebnis === 'offen'])>
                                    <td>{{ $punkt->bezeichnung }}<br><span class="leise klein">{{ status_text($punkt->art) }}</span></td>
                                    <td>
                                        @if ($punkt->art === 'messwert')
                                            {{ zahl($punkt->sollwert, 3) }} {{ $punkt->einheit }}<br>
                                            <span class="leise klein">{{ zahl($punkt->untere_grenze, 3) }} bis {{ zahl($punkt->obere_grenze, 3) }}</span>
                                        @else
                                            –
                                        @endif
                                    </td>
                                    <td>
                                        @if ($punkt->art === 'messwert')
                                            <input type="text" inputmode="decimal" class="eingabe-kurz" wire:model="istwert.{{ $punkt->id }}"
                                                   placeholder="{{ $punkt->istwert !== null ? zahl($punkt->istwert, 3) : 'Messwert' }}" aria-label="Messwert {{ $punkt->bezeichnung }}">
                                            @error('istwert.'.$punkt->id)<br><small class="ueber">{{ $message }}</small>@enderror
                                        @else
                                            @error('istwert.'.$punkt->id)<small class="ueber">{{ $message }}</small>@enderror
                                        @endif
                                    </td>
                                    <td><input type="text" wire:model="pruefBemerkung.{{ $punkt->id }}" placeholder="{{ $punkt->bemerkung ?? 'optional' }}" maxlength="255" aria-label="Bemerkung {{ $punkt->bezeichnung }}"></td>
                                    <td>
                                        <span class="badge {{ status_klasse($punkt->ergebnis) }}">{{ status_text($punkt->ergebnis) }}</span>
                                        @if ($punkt->istwert !== null)<br><span class="klein">{{ zahl($punkt->istwert, 3) }} {{ $punkt->einheit }}</span>@endif
                                    </td>
                                    <td>
                                        <div class="knopfleiste ohne-abstand">
                                            @if ($punkt->art === 'messwert')
                                                <button wire:click="pruefen({{ $punkt->id }})" class="btn btn-klein btn-primaer" wire:loading.attr="disabled">Speichern</button>
                                            @else
                                                <button wire:click="pruefen({{ $punkt->id }}, 'in_ordnung')" class="btn btn-klein btn-erfolg" wire:loading.attr="disabled">i. O.</button>
                                                <button wire:click="pruefen({{ $punkt->id }}, 'nicht_in_ordnung')" class="btn btn-klein btn-gefahr" wire:loading.attr="disabled">n. i. O.</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    @endif

    <section class="karte">
        <h2>Arbeitsvorrat an {{ $plaetze->firstWhere('nummer', $arbeitsplatz)?->bezeichnung ?? 'diesem Arbeitsplatz' }}</h2>
        <div class="tabelle-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Auftrag</th><th>Arbeitsgang</th><th>Artikel</th><th class="zahl">Menge</th>
                        <th class="zahl">Soll-Zeit</th><th>Liefertermin</th><th>Bereit?</th><th>Starten</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vorrat as $zeile)
                        <tr wire:key="vorrat-{{ $zeile->arbeitsgang_id }}" @class(['hervorgehoben' => $aktuell?->id === $zeile->arbeitsgang_id])>
                            <td><a href="{{ route('auftrag', $zeile->auftragsnummer) }}"><strong>{{ $zeile->auftragsnummer }}</strong></a></td>
                            <td>{{ $zeile->ag_nummer }} {{ $zeile->arbeitsgang }}</td>
                            <td>{{ $zeile->artikelnummer }} {{ $zeile->artikel }}</td>
                            <td class="zahl">{{ $zeile->menge }}</td>
                            <td class="zahl">{{ minuten($zeile->soll_zeit_min) }}</td>
                            <td>{{ datum($zeile->liefertermin) }}</td>
                            <td>
                                <span class="badge {{ status_klasse($zeile->bereitschaft) }}">
                                    {{ $zeile->bereitschaft === 'wartet' ? 'wartet auf AG '.$zeile->wartet_auf_ag : status_text($zeile->bereitschaft) }}
                                </span>
                            </td>
                            <td>
                                @if ($zeile->bereitschaft === 'bereit' && ! $aktuell)
                                    <div class="knopfleiste ohne-abstand">
                                        <button wire:click="starten({{ $zeile->arbeitsgang_id }}, 'ruesten')" class="btn btn-klein btn-primaer" wire:loading.attr="disabled">Rüsten</button>
                                        <button wire:click="starten({{ $zeile->arbeitsgang_id }}, 'bearbeiten')" class="btn btn-klein btn-sekundaer" wire:loading.attr="disabled">Bearbeiten</button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">Kein freigegebener Arbeitsvorrat an diesem Arbeitsplatz.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
