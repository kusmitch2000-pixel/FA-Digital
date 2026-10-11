<div>
    <div class="seitenkopf">
        <div>
            <h1>Artikel &amp; Arbeitspläne</h1>
            <p class="leise">Soll-Zeiten je Arbeitsgang, Prüfmerkmale für die Checkliste und Vorschläge aus den Ist-Zeiten</p>
        </div>
    </div>

    @error('aktion')<div class="hinweis fehler" role="alert">{{ $message }}</div>@enderror

    <section class="karte">
        <h2>Artikel</h2>
        <div class="tabelle-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Artikel-Nr.</th><th>Bezeichnung</th><th class="zahl">Arbeitsgänge</th><th class="zahl">Rüstzeit gesamt</th>
                        <th class="zahl">Stückzeit gesamt</th><th>Abgleich mit Ist-Zeiten</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($liste as $eintrag)
                        <tr wire:key="artikel-{{ $eintrag->id }}" @class(['hervorgehoben' => $eintrag->artikelnummer === $artikel])>
                            <td><strong>{{ $eintrag->artikelnummer }}</strong></td>
                            <td>{{ $eintrag->bezeichnung }}</td>
                            <td class="zahl">{{ $eintrag->positionen->count() }}</td>
                            <td class="zahl">{{ minuten($eintrag->positionen->sum('ruestzeit_min')) }}</td>
                            <td class="zahl">{{ minuten($eintrag->positionen->sum('stueckzeit_min')) }}</td>
                            <td>
                                @if ($anzahl = $offeneVorschlaege->get($eintrag->artikelnummer))
                                    <span class="badge unterbrochen">{{ $anzahl }} {{ $anzahl === 1 ? 'Vorschlag' : 'Vorschläge' }}</span>
                                @else
                                    <span class="leise klein">passt</span>
                                @endif
                            </td>
                            <td><button wire:click="auswaehlen('{{ $eintrag->artikelnummer }}')" class="btn btn-klein btn-sekundaer">Ansehen</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($gewaehlt)
        <section class="karte">
            <h2>Arbeitsplan {{ $gewaehlt->artikelnummer }} {{ $gewaehlt->bezeichnung }}</h2>
            <div class="tabelle-scroll">
                <table>
                    <thead>
                        <tr><th>AG</th><th>Arbeitsgang</th><th>Arbeitsplatz</th><th class="zahl">Rüstzeit</th><th class="zahl">Stückzeit</th><th>Prüfmerkmale (Checkliste)</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($gewaehlt->positionen as $position)
                            <tr wire:key="position-{{ $position->id }}">
                                <td>{{ $position->ag_nummer }}</td>
                                <td>{{ $position->bezeichnung }}</td>
                                <td>{{ $position->arbeitsplatz->bezeichnung }}</td>
                                <td class="zahl">{{ minuten($position->ruestzeit_min) }}</td>
                                <td class="zahl">{{ minuten($position->stueckzeit_min) }}</td>
                                <td class="klein">
                                    @forelse ($position->pruefmerkmale as $merkmal)
                                        {{ $merkmal->bezeichnung }}
                                        @if ($merkmal->art === 'messwert')<span class="leise">({{ zahl($merkmal->untere_grenze, 3) }} bis {{ zahl($merkmal->obere_grenze, 3) }} {{ $merkmal->einheit }})</span>@endif
                                        @if ($merkmal->zertifikatsart)<span class="badge freigegeben" title="nur bei Werkszertifikat {{ $merkmal->zertifikatsart->kuerzel }}">{{ $merkmal->zertifikatsart->kuerzel }}</span>@endif
                                        <br>
                                    @empty
                                        <span class="leise">keine</span>
                                    @endforelse
                                </td>
                                <td><button wire:click="positionEntfernen({{ $position->id }})" wire:confirm="AG {{ $position->ag_nummer }} aus dem Arbeitsplan entfernen?" class="btn btn-klein btn-sekundaer">Entfernen</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="7">Noch keine Arbeitsgänge.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="leise klein">Prüfmerkmale mit Kürzel prüft der Werker nur, wenn der Kunde dieses Werkszertifikat fordert.</p>
        </section>

        {{-- Rückmeldung vom 01.10.2026: Abweichungen in die neue Kalkulation übernehmen --}}
        <section class="karte">
            <h2>Soll-Zeiten mit Ist-Werten abgleichen: {{ $gewaehlt->artikelnummer }}</h2>
            <p class="leise klein">Ø Ist aus abgeschlossenen Aufträgen. Ein Vorschlag erscheint ab mehr als 10 % Abweichung. Laufende Aufträge behalten ihre Soll-Zeit.</p>
            <div class="tabelle-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>AG</th><th class="zahl">Aufträge</th><th class="zahl">Rüsten Soll</th><th class="zahl">Rüsten Ø Ist</th>
                            <th class="zahl">Stück Soll</th><th class="zahl">Stück Ø Ist</th><th>Vorschlag</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vorschlaege as $vorschlag)
                            <tr wire:key="vorschlag-{{ $vorschlag->arbeitsplan_position_id }}">
                                <td>{{ $vorschlag->ag_nummer }} {{ $vorschlag->arbeitsgang }}</td>
                                <td class="zahl">{{ $vorschlag->anzahl_auftraege }}</td>
                                <td class="zahl">{{ minuten($vorschlag->soll_ruestzeit_min) }}</td>
                                <td class="zahl"><span @class(['ueber' => $vorschlag->vorschlag_ruestzeit_min !== null])>{{ zahl($vorschlag->ist_ruestzeit_min, 1) }} min</span> <span class="leise klein">({{ prozent($vorschlag->abweichung_ruesten_prozent) }})</span></td>
                                <td class="zahl">{{ zahl($vorschlag->soll_stueckzeit_min, 2) }} min</td>
                                <td class="zahl"><span @class(['ueber' => $vorschlag->vorschlag_stueckzeit_min !== null])>{{ zahl($vorschlag->ist_stueckzeit_min, 2) }} min</span> <span class="leise klein">({{ prozent($vorschlag->abweichung_stueck_prozent) }})</span></td>
                                <td>
                                    <div class="knopfleiste ohne-abstand">
                                        @if ($vorschlag->vorschlag_ruestzeit_min !== null)
                                            <button wire:click="uebernehmen({{ $vorschlag->arbeitsplan_position_id }}, 'ruestzeit')" wire:confirm="Rüstzeit auf {{ zahl($vorschlag->vorschlag_ruestzeit_min, 1) }} min setzen?" class="btn btn-klein btn-primaer">Rüstzeit {{ zahl($vorschlag->vorschlag_ruestzeit_min, 1) }} min übernehmen</button>
                                        @endif
                                        @if ($vorschlag->vorschlag_stueckzeit_min !== null)
                                            <button wire:click="uebernehmen({{ $vorschlag->arbeitsplan_position_id }}, 'stueckzeit')" wire:confirm="Stückzeit auf {{ zahl($vorschlag->vorschlag_stueckzeit_min, 2) }} min setzen?" class="btn btn-klein btn-primaer">Stückzeit {{ zahl($vorschlag->vorschlag_stueckzeit_min, 2) }} min übernehmen</button>
                                        @endif
                                        @if ($vorschlag->vorschlag_ruestzeit_min === null && $vorschlag->vorschlag_stueckzeit_min === null)
                                            <span class="leise klein">passt (unter 10 %)</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">Noch kein abgeschlossener Auftrag für diesen Artikel.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @php($aenderungen = $gewaehlt->positionen->flatMap(fn ($p) => $p->aenderungen->map(fn ($a) => [$p, $a]))->sortByDesc(fn ($paar) => $paar[1]->geaendert_am))
            @if ($aenderungen->isNotEmpty())
                <h3>Bisherige Änderungen</h3>
                <div class="tabelle-scroll">
                    <table>
                        <thead><tr><th>Datum</th><th>AG</th><th>Feld</th><th class="zahl">Alt</th><th class="zahl">Neu</th><th>Begründung</th><th>Von</th></tr></thead>
                        <tbody>
                            @foreach ($aenderungen as [$position, $aenderung])
                                <tr>
                                    <td>{{ zeitpunkt($aenderung->geaendert_am) }}</td>
                                    <td>{{ $position->ag_nummer }} {{ $position->bezeichnung }}</td>
                                    <td>{{ $aenderung->feld === 'ruestzeit' ? 'Rüstzeit' : 'Stückzeit' }}</td>
                                    <td class="zahl">{{ zahl($aenderung->alter_wert, 2) }} min</td>
                                    <td class="zahl">{{ zahl($aenderung->neuer_wert, 2) }} min</td>
                                    <td class="klein">{{ $aenderung->begruendung }}</td>
                                    <td>{{ $aenderung->geaendertVon->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <div class="spalten-gleich">
            <form wire:submit="positionHinzufuegen" class="karte">
                <h2>Arbeitsgang hinzufügen</h2>
                <div class="formular-raster">
                    <div class="feld">
                        <label for="ag-nummer">AG-Nummer</label>
                        <input id="ag-nummer" type="number" min="1" wire:model="agNummer">
                        @error('agNummer')<small class="ueber">{{ $message }}</small>@enderror
                    </div>
                    <div class="feld">
                        <label for="ag-bezeichnung">Bezeichnung</label>
                        <input id="ag-bezeichnung" wire:model="agBezeichnung" maxlength="80">
                        @error('agBezeichnung')<small class="ueber">{{ $message }}</small>@enderror
                    </div>
                    <div class="feld">
                        <label for="ag-platz">Arbeitsplatz</label>
                        <select id="ag-platz" wire:model="arbeitsplatzId">
                            @foreach ($plaetze as $platz)
                                <option value="{{ $platz->id }}">{{ $platz->nummer }} {{ $platz->bezeichnung }}</option>
                            @endforeach
                        </select>
                        @error('arbeitsplatzId')<small class="ueber">{{ $message }}</small>@enderror
                    </div>
                    <div class="feld">
                        <label for="ag-ruesten">Rüstzeit (min)</label>
                        <input id="ag-ruesten" type="number" min="0" step="0.01" wire:model="ruestzeit">
                        @error('ruestzeit')<small class="ueber">{{ $message }}</small>@enderror
                    </div>
                    <div class="feld">
                        <label for="ag-stueck">Stückzeit (min)</label>
                        <input id="ag-stueck" type="number" min="0" step="0.01" wire:model="stueckzeit">
                        @error('stueckzeit')<small class="ueber">{{ $message }}</small>@enderror
                    </div>
                </div>
                <button class="btn btn-primaer" type="submit">Arbeitsgang hinzufügen</button>
            </form>

            <form wire:submit="artikelAnlegen" class="karte">
                <h2>Neuer Artikel</h2>
                <div class="feld">
                    <label for="artikelnummer">Artikel-Nr.</label>
                    <input id="artikelnummer" wire:model="artikelnummer" maxlength="20">
                    @error('artikelnummer')<small class="ueber">{{ $message }}</small>@enderror
                </div>
                <div class="feld">
                    <label for="artikel-bezeichnung">Bezeichnung</label>
                    <input id="artikel-bezeichnung" wire:model="bezeichnung" maxlength="100">
                    @error('bezeichnung')<small class="ueber">{{ $message }}</small>@enderror
                </div>
                <button class="btn btn-sekundaer" type="submit">Artikel anlegen</button>
            </form>
        </div>
    @endif
</div>
