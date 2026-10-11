<div>
    <div class="seitenkopf">
        <div>
            <h1>Nachkalkulation {{ $kopf?->auftragsnummer }}</h1>
            <p class="leise">
                @if ($kopf)
                    {{ $kopf->artikelnummer }} {{ $kopf->artikel }} · {{ $kopf->menge }} Stück · fertig seit {{ zeitpunkt($kopf->abgeschlossen_am) }}
                @else
                    Soll-Ist-Vergleich auf Basis der erfassten Arbeitszeit
                @endif
            </p>
        </div>
        <div class="knopfleiste">
            <label for="auftrag" class="leise ohne-abstand">Auftrag</label>
            <select id="auftrag" wire:model.live="auftrag" class="schmal">
                @foreach ($auswahl as $eintrag)
                    <option value="{{ $eintrag->auftragsnummer }}">{{ $eintrag->auftragsnummer }} · {{ $eintrag->artikel }} ({{ status_text($eintrag->status) }})</option>
                @endforeach
            </select>
        </div>
    </div>

    @error('aktion')<div class="hinweis fehler" role="alert">{{ $message }}</div>@enderror

    @if ($kopf)
        {{-- Kachelfarbe wie die Ampel: bis +5 % grün, bis +10 % gelb, darüber rot --}}
        @php($kachel = ['gruen' => 'fertig', 'gelb' => 'in-arbeit', 'rot' => 'verzug'])
        @php($zeitAbweichung = $kopf->ist_zeit_min - $kopf->soll_zeit_min)
        @php($stueckAbweichung = $kopf->soll_kosten_je_stueck > 0 && $kopf->ist_kosten_je_gutstueck !== null ? 100 * ($kopf->ist_kosten_je_gutstueck / $kopf->soll_kosten_je_stueck - 1) : null)
        <section class="kacheln">
            <div class="kachel">
                <span class="text">Soll-Kosten</span>
                <span class="zahl">{{ euro($kopf->soll_kosten) }}</span>
                <span class="text">{{ minuten($kopf->soll_zeit_min) }} geplant</span>
            </div>
            <div class="kachel {{ $kachel[$kopf->ampel] }}">
                <span class="text">Ist-Kosten</span>
                <span class="zahl">{{ euro($kopf->ist_kosten) }}</span>
                <span class="text"><span class="ampel {{ $kopf->ampel }}"></span>{{ $kopf->ist_kosten >= $kopf->soll_kosten ? '+' : '' }}{{ euro($kopf->ist_kosten - $kopf->soll_kosten) }} ({{ prozent($kopf->abweichung_prozent) }})</span>
            </div>
            <div class="kachel">
                <span class="text">Ist-Zeit</span>
                <span class="zahl">{{ minuten($kopf->ist_zeit_min) }}</span>
                <span class="text">{{ $zeitAbweichung >= 0 ? '+' : '' }}{{ minuten($zeitAbweichung) }} ({{ prozent($kopf->soll_zeit_min > 0 ? 100 * $zeitAbweichung / $kopf->soll_zeit_min : null) }})</span>
            </div>
            <div class="kachel {{ $stueckAbweichung > 10 ? 'verzug' : ($stueckAbweichung > 5 ? 'in-arbeit' : 'fertig') }}">
                <span class="text">Kosten pro Gutstück</span>
                <span class="zahl">{{ euro($kopf->ist_kosten_je_gutstueck) }}</span>
                <span class="text">Soll {{ euro($kopf->soll_kosten_je_stueck) }} · {{ prozent($stueckAbweichung) }}</span>
            </div>
        </section>

        <section class="karte">
            <h2>Soll-Ist-Vergleich je Arbeitsgang</h2>
            <div class="tabelle-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>AG</th><th>Arbeitsplatz</th><th class="zahl">Satz</th><th class="zahl">Soll-Zeit</th><th class="zahl">Ist-Zeit</th><th class="zahl">Abw. Zeit</th>
                            <th class="zahl">Soll-Kosten</th><th class="zahl">Ist-Kosten</th><th class="zahl">Abw. Kosten</th><th>Bewertung</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($zeilen as $zeile)
                            <tr>
                                <td>{{ $zeile->ag_nummer }} {{ $zeile->arbeitsgang }}</td>
                                <td>{{ $zeile->arbeitsplatz }}</td>
                                <td class="zahl">{{ euro($zeile->stundensatz) }}/h</td>
                                <td class="zahl">{{ minuten($zeile->soll_zeit_min) }}</td>
                                <td class="zahl">{{ minuten($zeile->ist_zeit_min) }}</td>
                                <td class="zahl">{{ $zeile->ist_zeit_min > $zeile->soll_zeit_min ? '+' : '' }}{{ minuten($zeile->ist_zeit_min - $zeile->soll_zeit_min) }}</td>
                                <td class="zahl">{{ euro($zeile->soll_kosten) }}</td>
                                <td class="zahl">{{ euro($zeile->ist_kosten) }}</td>
                                <td class="zahl">{{ $zeile->ist_kosten > $zeile->soll_kosten ? '+' : '' }}{{ euro($zeile->ist_kosten - $zeile->soll_kosten) }}</td>
                                <td><span class="ampel {{ $zeile->ampel }}"></span>{{ prozent($zeile->abweichung_prozent) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">Gesamt</td>
                            <td class="zahl">{{ minuten($kopf->soll_zeit_min) }}</td>
                            <td class="zahl">{{ minuten($kopf->ist_zeit_min) }}</td>
                            <td></td>
                            <td class="zahl">{{ euro($kopf->soll_kosten) }}</td>
                            <td class="zahl">{{ euro($kopf->ist_kosten) }}</td>
                            <td class="zahl">{{ $kopf->ist_kosten > $kopf->soll_kosten ? '+' : '' }}{{ euro($kopf->ist_kosten - $kopf->soll_kosten) }}</td>
                            <td><span class="ampel {{ $kopf->ampel }}"></span>{{ prozent($kopf->abweichung_prozent) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="legende">
                <span><span class="ampel gruen"></span>bis +5 %</span>
                <span><span class="ampel gelb"></span>bis +10 %</span>
                <span><span class="ampel rot"></span>über +10 %</span>
            </p>
        </section>

        <div class="spalten-gleich">
            <section class="karte">
                <h2>Rüsten und Bearbeiten getrennt</h2>
                <div class="tabelle-scroll">
                    <table>
                        <thead><tr><th>AG</th><th class="zahl">Rüsten Soll / Ist</th><th class="zahl">Bearbeiten Soll / Ist</th></tr></thead>
                        <tbody>
                            @foreach ($zeilen as $zeile)
                                <tr>
                                    <td>{{ $zeile->ag_nummer }} {{ $zeile->arbeitsgang }}</td>
                                    <td class="zahl">{{ zahl($zeile->soll_ruesten_min) }} / <span @class(['ueber' => $zeile->ist_ruesten_min > $zeile->soll_ruesten_min])>{{ minuten($zeile->ist_ruesten_min) }}</span></td>
                                    <td class="zahl">{{ zahl($zeile->soll_bearbeiten_min) }} / <span @class(['ueber' => $zeile->ist_bearbeiten_min > $zeile->soll_bearbeiten_min])>{{ minuten($zeile->ist_bearbeiten_min) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="karte">
                <h2>Ergebnis</h2>
                <p>
                    Gutmenge {{ $kopf->gutmenge }} von {{ $kopf->menge }} Stück, Ausschuss {{ $kopf->ausschuss }}.
                    Die Ist-Kosten liegen {{ $kopf->abweichung_prozent >= 0 ? 'um '.prozent($kopf->abweichung_prozent, false).' über' : 'um '.prozent(-$kopf->abweichung_prozent, false).' unter' }} dem Soll.
                </p>
                <p class="leise klein">Unterbrechungen sind in den Ist-Kosten nicht enthalten. Sie stehen getrennt in der Auswertung.</p>
                @if ($vorschlaege > 0)
                    <div class="hinweis warnung">
                        Für {{ $kopf->artikelnummer }} gibt es {{ $vorschlaege }} {{ $vorschlaege === 1 ? 'Vorschlag' : 'Vorschläge' }}, die Soll-Zeit anzupassen.
                        @if (auth()->user()->darf('arbeitsplaene'))
                            <a href="{{ route('arbeitsplaene', ['artikel' => $kopf->artikelnummer]) }}">Zum Arbeitsplan</a>
                        @else
                            Die Arbeitsvorbereitung kann sie übernehmen.
                        @endif
                    </div>
                @endif
                @if ($kopf->status === 'fertig' && $darfAbschliessen)
                    <button wire:click="abschliessen" wire:confirm="Nachkalkulation für {{ $kopf->auftragsnummer }} abschließen? Danach lassen sich die Buchungen nicht mehr ändern." class="btn btn-primaer">Nachkalkulation abschließen</button>
                @elseif ($kopf->status === 'nachkalkuliert')
                    <p><span class="badge nachkalkuliert">nachkalkuliert</span></p>
                @endif
            </section>
        </div>
    @else
        <section class="karte">Noch kein Auftrag ist fertig gemeldet.</section>
    @endif
</div>
