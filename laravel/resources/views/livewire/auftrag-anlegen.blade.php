<div>
    <div class="seitenkopf">
        <div>
            <h1>Neuer Fertigungsauftrag</h1>
            <p class="leise">Arbeitsgänge, Soll-Kosten und Checklisten entstehen aus dem Arbeitsplan des Artikels.</p>
        </div>
    </div>

    @error('aktion')<div class="hinweis fehler" role="alert">{{ $message }}</div>@enderror

    <form wire:submit="speichern">
        <section class="karte">
            <h2>Auftragsdaten</h2>
            <div class="formular-raster">
                <div class="feld">
                    <label for="artikel">Artikel</label>
                    <select id="artikel" wire:model.live="artikelId">
                        <option value="">Bitte wählen</option>
                        @foreach ($artikel as $eintrag)
                            <option value="{{ $eintrag->id }}">{{ $eintrag->artikelnummer }} {{ $eintrag->bezeichnung }}</option>
                        @endforeach
                    </select>
                    @error('artikelId')<small class="ueber">{{ $message }}</small>@enderror
                </div>
                <div class="feld">
                    <label for="menge">Menge (Stück)</label>
                    <input id="menge" type="number" min="1" wire:model.live.debounce.300ms="menge">
                    @error('menge')<small class="ueber">{{ $message }}</small>@enderror
                </div>
                <div class="feld">
                    <label for="liefertermin">Liefertermin</label>
                    <input id="liefertermin" type="date" wire:model="liefertermin">
                    @error('liefertermin')<small class="ueber">{{ $message }}</small>@enderror
                </div>
                <div class="feld">
                    <label for="kunde">Kunde oder Kommission</label>
                    <input id="kunde" wire:model="kunde" maxlength="100">
                    @error('kunde')<small class="ueber">{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="feld">
                <label for="bemerkung">Bemerkung</label>
                <textarea id="bemerkung" wire:model="bemerkung" maxlength="255"></textarea>
                @error('bemerkung')<small class="ueber">{{ $message }}</small>@enderror
            </div>
        </section>

        {{-- Rückmeldung vom 01.10.2026: Prüfzertifikate schon bei der Auftragserstellung angeben --}}
        <section class="karte">
            <h2>Werkszertifikate, die der Kunde fordert</h2>
            <p class="leise klein">Je Zertifikat bekommt der Werker zusätzliche Prüfpunkte in seiner Checkliste.</p>
            <div class="gruende">
                @foreach ($zertifikatsarten as $art)
                    <label class="grund" title="{{ $art->beschreibung }}">
                        <input type="checkbox" wire:model.live="zertifikate" value="{{ $art->id }}"> {{ $art->kuerzel }} {{ $art->bezeichnung }}
                    </label>
                @endforeach
            </div>
            @error('zertifikate.*')<small class="ueber">{{ $message }}</small>@enderror
        </section>

        <section class="karte">
            <h2>Vorschau: Arbeitsgänge, Soll-Kosten und Checkliste</h2>
            <div class="tabelle-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>AG</th><th>Arbeitsgang</th><th>Arbeitsplatz</th><th class="zahl">Rüstzeit</th><th class="zahl">Stückzeit</th>
                            <th class="zahl">Soll-Zeit</th><th class="zahl">Stundensatz</th><th class="zahl">Soll-Kosten</th><th>Prüfpunkte für den Werker</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vorschau as $zeile)
                            <tr>
                                <td>{{ $zeile->position->ag_nummer }}</td>
                                <td>{{ $zeile->position->bezeichnung }}</td>
                                <td>{{ $zeile->position->arbeitsplatz->bezeichnung }}</td>
                                <td class="zahl">{{ minuten($zeile->position->ruestzeit_min) }}</td>
                                <td class="zahl">{{ minuten($zeile->position->stueckzeit_min) }}</td>
                                <td class="zahl">{{ minuten($zeile->soll_zeit) }}</td>
                                <td class="zahl">{{ euro($zeile->position->arbeitsplatz->stundensatz) }}/h</td>
                                <td class="zahl">{{ euro($zeile->soll_kosten) }}</td>
                                <td class="klein">
                                    @forelse ($zeile->pruefpunkte as $merkmal)
                                        {{ $merkmal->bezeichnung }}@if ($merkmal->zertifikatsart) <span class="badge freigegeben">{{ $merkmal->zertifikatsart->kuerzel }}</span>@endif<br>
                                    @empty
                                        <span class="leise">keine</span>
                                    @endforelse
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9">Bitte einen Artikel wählen.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($vorschau->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="5">Summe</td>
                                <td class="zahl">{{ minuten($vorschau->sum('soll_zeit')) }}</td>
                                <td></td>
                                <td class="zahl">{{ euro($vorschau->sum('soll_kosten')) }}</td>
                                @php($anzahlPruefpunkte = $vorschau->sum(fn ($zeile) => $zeile->pruefpunkte->count()))
                                <td>{{ $anzahlPruefpunkte }} {{ $anzahlPruefpunkte === 1 ? 'Prüfpunkt' : 'Prüfpunkte' }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </section>

        <div class="knopfleiste">
            <button type="submit" class="btn btn-sekundaer" wire:loading.attr="disabled">Speichern</button>
            <button type="button" wire:click="speichern(true)" class="btn btn-primaer" wire:loading.attr="disabled">Speichern und freigeben</button>
            <a class="btn btn-sekundaer" href="{{ route('auftraege') }}">Abbrechen</a>
        </div>
    </form>
</div>
