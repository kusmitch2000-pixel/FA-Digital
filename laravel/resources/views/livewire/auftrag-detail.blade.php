<div>
    <p class="klein"><a href="{{ route('auftraege') }}">← Zurück zu den Aufträgen</a></p>

    <div class="seitenkopf">
        <div>
            <h1>Auftrag {{ $auftrag->auftragsnummer }} <span class="badge {{ status_klasse($auftrag->status) }}">{{ status_text($auftrag->status) }}</span></h1>
            <p class="leise">{{ $auftrag->artikel->artikelnummer }} {{ $auftrag->artikel->bezeichnung }} · {{ $auftrag->menge }} Stück · Liefertermin {{ datum($auftrag->liefertermin) }}</p>
        </div>
        <div class="knopfleiste">
            @if ($darfFreigeben && $auftrag->status === 'angelegt')
                <button wire:click="freigeben" class="btn btn-primaer" wire:loading.attr="disabled">Freigeben</button>
            @endif
            @if ($darfFreigeben && in_array($auftrag->status, ['angelegt', 'freigegeben'], true) && $buchungen->isEmpty())
                <button wire:click="stornieren" wire:confirm="Auftrag {{ $auftrag->auftragsnummer }} wirklich stornieren?" class="btn btn-gefahr">Stornieren</button>
            @endif
            @if (in_array($auftrag->status, ['fertig', 'nachkalkuliert'], true) && auth()->user()->darf('nachkalkulation'))
                <a class="btn btn-sekundaer" href="{{ route('nachkalkulation', ['auftrag' => $auftrag->auftragsnummer]) }}">Nachkalkulation</a>
            @endif
        </div>
    </div>

    @error('aktion')<div class="hinweis fehler" role="alert">{{ $message }}</div>@enderror

    <div class="spalten-gleich">
        <section class="karte">
            <h2>Kopfdaten</h2>
            <dl class="kopfdaten">
                <dt>Kunde</dt><dd>{{ $auftrag->kunde ?? '–' }}</dd>
                <dt>Angelegt</dt><dd>{{ zeitpunkt($auftrag->angelegt_am) }} von {{ $auftrag->angelegtVon->name }}</dd>
                <dt>Freigegeben</dt><dd>{{ zeitpunkt($auftrag->freigegeben_am) }}</dd>
                <dt>Fertig</dt><dd>{{ zeitpunkt($auftrag->abgeschlossen_am) }}</dd>
                <dt>Bemerkung</dt><dd>{{ $auftrag->bemerkung ?? '–' }}</dd>
            </dl>
        </section>
        <section class="karte">
            <h2>Werkszertifikate</h2>
            @forelse ($auftrag->zertifikate as $zertifikat)
                <p>
                    <strong>{{ $zertifikat->zertifikatsart->kuerzel }}</strong> {{ $zertifikat->zertifikatsart->bezeichnung }}
                    <span class="badge {{ status_klasse($zertifikat->status) }}">{{ $zertifikat->status }}</span>
                    @if (auth()->user()->darf('zertifikate'))
                        · <a href="{{ route('zertifikat.druck', $zertifikat) }}" target="_blank">Werkszeugnis ansehen</a>
                    @endif
                </p>
            @empty
                <p class="leise">Der Kunde fordert kein Werkszertifikat. Der Werker prüft nur die Standardpunkte.</p>
            @endforelse
        </section>
    </div>

    <section class="karte">
        <h2>Arbeitsgänge</h2>
        <div class="tabelle-scroll">
            <table>
                <thead>
                    <tr>
                        <th>AG</th><th>Arbeitsgang</th><th>Arbeitsplatz</th><th>Status</th><th class="zahl">Soll-Zeit</th><th class="zahl">Ist-Zeit</th>
                        <th class="zahl">Soll-Kosten</th><th class="zahl">Ist-Kosten</th><th>Gut / Ausschuss</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($auftrag->arbeitsgaenge as $arbeitsgang)
                        @php($zeit = $zeiten->get($arbeitsgang->id))
                        <tr wire:key="ag-{{ $arbeitsgang->id }}">
                            <td>{{ $arbeitsgang->ag_nummer }}</td>
                            <td>{{ $arbeitsgang->bezeichnung }}</td>
                            <td>{{ $arbeitsgang->arbeitsplatz->bezeichnung }}</td>
                            <td><span class="badge {{ status_klasse($arbeitsgang->status) }}">{{ status_text($arbeitsgang->status) }}</span></td>
                            <td class="zahl">{{ minuten($zeit->soll_zeit_min) }}</td>
                            <td class="zahl">
                                <span @class(['ueber' => $zeit->ist_zeit_min > $zeit->soll_zeit_min])>{{ minuten($zeit->ist_zeit_min) }}</span>
                            </td>
                            <td class="zahl">{{ euro($zeit->soll_kosten) }}</td>
                            <td class="zahl">{{ euro($zeit->ist_kosten) }}</td>
                            <td>{{ $arbeitsgang->status === 'erledigt' ? $arbeitsgang->gutmenge.' / '.$arbeitsgang->ausschuss : '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($checkliste->isNotEmpty())
        <section class="karte">
            <h2>Checkliste</h2>
            <div class="tabelle-scroll">
                <table>
                    <thead>
                        <tr><th>AG</th><th>Prüfpunkt</th><th>Soll / Toleranz</th><th>Ist</th><th>Ergebnis</th><th>Geprüft</th><th>Bemerkung</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($checkliste as $punkt)
                            <tr>
                                <td>{{ $punkt->ag_nummer }}</td>
                                <td>{{ $punkt->pruefpunkt }}</td>
                                <td>
                                    @if ($punkt->art === 'messwert')
                                        {{ zahl($punkt->sollwert, 3) }} {{ $punkt->einheit }} <span class="leise klein">({{ zahl($punkt->untere_grenze, 3) }} bis {{ zahl($punkt->obere_grenze, 3) }})</span>
                                    @else
                                        Sichtprüfung
                                    @endif
                                </td>
                                <td>{{ $punkt->istwert !== null ? zahl($punkt->istwert, 3).' '.$punkt->einheit : '–' }}</td>
                                <td><span class="badge {{ status_klasse($punkt->ergebnis) }}">{{ status_text($punkt->ergebnis) }}</span></td>
                                <td class="klein">{{ $punkt->geprueft_von ? $punkt->geprueft_von.', '.zeitpunkt($punkt->geprueft_am) : '–' }}</td>
                                <td class="klein">{{ $punkt->bemerkung }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="karte">
        <h2>Zeitbuchungen</h2>
        <p class="leise klein">Korrigierte Buchungen bleiben durchgestrichen sichtbar. So ist jede Änderung nachvollziehbar.</p>
        <div class="tabelle-scroll">
            <table>
                <thead>
                    <tr>
                        <th>AG</th><th>Mitarbeiter</th><th>Tätigkeit</th><th>Beginn</th><th>Ende</th><th class="zahl">Dauer</th>
                        <th>Quelle</th><th>Begründung</th>@if ($darfKorrigieren)<th></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($buchungen as $buchung)
                        <tr wire:key="buchung-{{ $buchung->id }}" @class(['ungueltig' => ! $buchung->gueltig, 'hervorgehoben' => $korrekturVon === $buchung->id])>
                            <td>{{ $buchung->arbeitsgang->ag_nummer }}</td>
                            <td>{{ $buchung->benutzer->name }}</td>
                            <td>{{ status_text($buchung->taetigkeit) }}</td>
                            <td>{{ zeitpunkt($buchung->beginn) }}</td>
                            <td>{!! $buchung->ende ? e(zeitpunkt($buchung->ende)) : '<span class="badge laeuft">läuft</span>' !!}</td>
                            <td class="zahl">{{ $buchung->ende ? minuten(floor($buchung->beginn->diffInMinutes($buchung->ende))) : '–' }}</td>
                            <td>
                                {{ ['terminal' => 'Terminal', 'nachtrag' => 'Nachtrag', 'korrektur' => 'Korrektur'][$buchung->quelle] }}
                                @if ($buchung->quelle !== 'terminal')<br><span class="leise klein">von {{ $buchung->erfasstVon->name }}</span>@endif
                                @unless ($buchung->gueltig)<br><span class="leise klein">ersetzt</span>@endunless
                            </td>
                            <td class="klein">{{ $buchung->begruendung }}</td>
                            @if ($darfKorrigieren)
                                <td>
                                    @if ($buchung->gueltig && $buchung->ende)
                                        <button wire:click="korrekturBeginnen({{ $buchung->id }})" class="btn btn-klein btn-sekundaer">Korrigieren</button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="9">Noch keine Buchungen.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($unterbrechungen->isNotEmpty())
            <h3>Unterbrechungen <span class="leise klein">(zählen nicht zur Ist-Zeit)</span></h3>
            <div class="tabelle-scroll">
                <table>
                    <thead><tr><th>Grund</th><th>Mitarbeiter</th><th>Beginn</th><th>Ende</th><th class="zahl">Dauer</th><th>Bemerkung</th></tr></thead>
                    <tbody>
                        @foreach ($unterbrechungen as $unterbrechung)
                            <tr>
                                <td><span class="badge unterbrochen">{{ $unterbrechung->grund->bezeichnung }}</span></td>
                                <td>{{ $unterbrechung->benutzer->name }}</td>
                                <td>{{ zeitpunkt($unterbrechung->beginn) }}</td>
                                <td>{{ $unterbrechung->ende ? zeitpunkt($unterbrechung->ende) : 'dauert an' }}</td>
                                <td class="zahl">{{ $unterbrechung->ende ? minuten(floor($unterbrechung->beginn->diffInMinutes($unterbrechung->ende))) : '–' }}</td>
                                <td class="klein">{{ $unterbrechung->bemerkung }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($darfKorrigieren)
        <form wire:submit="buchungSpeichern" class="karte">
            <h2>{{ $korrekturVon ? 'Buchung korrigieren' : 'Buchung nachtragen' }}</h2>
            <p class="leise klein">
                @if ($korrekturVon)
                    Die alte Buchung wird nicht gelöscht, sondern als ungültig markiert und durch die neue ersetzt.
                @else
                    Für vergessene Buchungen, z. B. wenn das Terminal ausgefallen war.
                @endif
            </p>
            <div class="formular-raster">
                <div class="feld">
                    <label for="arbeitsgang">Arbeitsgang</label>
                    <select id="arbeitsgang" wire:model="arbeitsgangId" @disabled($korrekturVon)>
                        @foreach ($auftrag->arbeitsgaenge as $arbeitsgang)
                            <option value="{{ $arbeitsgang->id }}">{{ $arbeitsgang->ag_nummer }} {{ $arbeitsgang->bezeichnung }}</option>
                        @endforeach
                    </select>
                    @error('arbeitsgangId')<small class="ueber">{{ $message }}</small>@enderror
                </div>
                <div class="feld">
                    <label for="werker">Mitarbeiter</label>
                    <select id="werker" wire:model="werkerId" @disabled($korrekturVon)>
                        <option value="">Bitte wählen</option>
                        @foreach ($werker as $person)
                            <option value="{{ $person->id }}">{{ $person->name }}</option>
                        @endforeach
                    </select>
                    @error('werkerId')<small class="ueber">{{ $message }}</small>@enderror
                </div>
                <div class="feld">
                    <label for="taetigkeit">Tätigkeit</label>
                    <select id="taetigkeit" wire:model="taetigkeit">
                        <option value="ruesten">Rüsten</option>
                        <option value="bearbeiten">Bearbeiten</option>
                    </select>
                    @error('taetigkeit')<small class="ueber">{{ $message }}</small>@enderror
                </div>
                <div class="feld">
                    <label for="beginn">Beginn</label>
                    <input id="beginn" type="datetime-local" wire:model="beginn">
                </div>
                <div class="feld">
                    <label for="ende">Ende</label>
                    <input id="ende" type="datetime-local" wire:model="ende">
                    @error('ende')<small class="ueber">{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="feld">
                <label for="begruendung">Begründung (Pflicht)</label>
                <input id="begruendung" wire:model="begruendung" maxlength="255" placeholder="z. B. Rüsten endete laut Werker nach 25 min">
                @error('begruendung')<small class="ueber">{{ $message }}</small>@enderror
            </div>
            <div class="knopfleiste">
                <button type="submit" class="btn btn-primaer" wire:loading.attr="disabled">{{ $korrekturVon ? 'Korrektur speichern' : 'Nachtrag speichern' }}</button>
                @if ($korrekturVon)
                    <button type="button" wire:click="nachtragBeginnen" class="btn btn-sekundaer">Abbrechen</button>
                @endif
            </div>
        </form>
    @endif
</div>
