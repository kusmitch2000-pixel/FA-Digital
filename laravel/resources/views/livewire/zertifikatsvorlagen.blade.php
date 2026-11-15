<div>
    <div class="seitenkopf">
        <div>
            <h1>Zertifikatsvorlagen</h1>
            <p class="leise">Welche Zertifikate ein Kunde bei der Auftragsanlage fordern kann. Neben den Zertifikaten nach DIN EN 10204 legt die Arbeitsvorbereitung eigene Prüfprotokolle an.</p>
        </div>
        @if ($darfAendern)
            <button type="button" wire:click="bearbeiten(null)" class="btn btn-primaer">+ Neues Zertifikat</button>
        @endif
    </div>

    @error('aktion')<div class="hinweis fehler" role="alert">{{ $message }}</div>@enderror
    @if ($meldung !== '')<div class="hinweis" role="status">{{ $meldung }}</div>@endif

    <section class="karte">
        <h2>Alle Zertifikate</h2>
        <div class="tabelle-scroll">
            <table>
                <thead>
                    <tr><th>Kürzel</th><th>Bezeichnung</th><th>Herkunft</th><th>Gilt für</th><th>Prüfmerkmale</th><th class="zahl">Aufträge</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($zertifikate as $zertifikat)
                        @php($eigene = $merkmale->get($zertifikat->id, collect()))
                        @php($norm = \App\Support\Zertifikate::istNorm($zertifikat))
                        <tr wire:key="zertifikat-{{ $zertifikat->id }}" @class(['hervorgehoben' => $bearbeitetId === $zertifikat->id])>
                            <td><strong>{{ $zertifikat->kuerzel }}</strong></td>
                            <td>{{ $zertifikat->bezeichnung }}<br><span class="leise klein">{{ $zertifikat->beschreibung }}</span></td>
                            <td><span class="badge {{ $norm ? 'offen' : 'freigegeben' }}">{{ $norm ? 'DIN EN 10204' : 'eigenes' }}</span></td>
                            <td class="klein">{{ $eigene->isEmpty() ? 'alle Artikel' : $eigene->pluck('artikelnummer')->unique()->join(', ') }}</td>
                            <td class="klein">
                                @forelse ($eigene as $merkmal)
                                    {{ $merkmal->artikelnummer }} · AG {{ $merkmal->ag_nummer }}: {{ $merkmal->bezeichnung }}<br>
                                @empty
                                    <span class="leise">keine, bestätigt nur die Lieferung</span>
                                @endforelse
                            </td>
                            <td class="zahl">{{ $verwendet->get($zertifikat->id, 0) }}</td>
                            <td>
                                @if ($darfAendern && ! $norm)
                                    <div class="knopfleiste ohne-abstand">
                                        <button type="button" wire:click="bearbeiten({{ $zertifikat->id }})" class="btn btn-klein btn-sekundaer">Bearbeiten</button>
                                        <button type="button" wire:click="loeschen({{ $zertifikat->id }})" wire:confirm="Zertifikat {{ $zertifikat->kuerzel }} löschen?" class="btn btn-klein btn-gefahr">Löschen</button>
                                    </div>
                                @elseif ($norm)
                                    <span class="leise klein">Norm</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="leise klein">Für welche Artikel ein Zertifikat gilt, ergibt sich aus seinen Prüfmerkmalen: Unter <a href="{{ route('arbeitsplaene') }}">Artikel &amp; Arbeitspläne</a> beim passenden Arbeitsgang ein Prüfmerkmal anlegen und das Zertifikat wählen. Ein Zertifikat ohne Prüfmerkmale gilt für alle Artikel. Fordert der Kunde das Zertifikat, kommen seine Prüfmerkmale zusätzlich in die Checkliste des Werkers.</p>
    </section>

    @if ($darfAendern)
        <form wire:submit="speichern" class="karte">
            <h2>{{ $bearbeitetId ? 'Zertifikat bearbeiten: '.$kuerzel : 'Neues Zertifikat' }}</h2>
            <div class="formular-raster">
                <div class="feld">
                    <label for="kuerzel">Kürzel</label>
                    <input id="kuerzel" wire:model="kuerzel" maxlength="10" placeholder="z. B. MP">
                    @error('kuerzel')<small class="ueber">{{ $message }}</small>@enderror
                </div>
                <div class="feld">
                    <label for="bezeichnung">Bezeichnung</label>
                    <input id="bezeichnung" wire:model="bezeichnung" maxlength="80" placeholder="z. B. Maßprotokoll">
                    @error('bezeichnung')<small class="ueber">{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="feld">
                <label for="beschreibung">Beschreibung</label>
                <input id="beschreibung" wire:model="beschreibung" maxlength="255" placeholder="Was das Zertifikat dem Kunden bestätigt">
                @error('beschreibung')<small class="ueber">{{ $message }}</small>@enderror
            </div>
            <div class="knopfleiste ohne-abstand">
                <button type="submit" class="btn btn-primaer">Speichern</button>
                @if ($bearbeitetId)
                    <button type="button" wire:click="bearbeiten(null)" class="btn btn-sekundaer">Abbrechen</button>
                @endif
            </div>
        </form>
    @endif
</div>
