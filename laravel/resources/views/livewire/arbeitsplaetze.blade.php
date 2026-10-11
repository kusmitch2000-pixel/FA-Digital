<div>
    <div class="seitenkopf">
        <div>
            <h1>Arbeitsplätze</h1>
            <p class="leise">Stundensätze werden bei der Auftragsanlage übernommen. Laufende Aufträge behalten ihren Satz.</p>
        </div>
        <button wire:click="bearbeiten(null)" class="btn btn-primaer">+ Neuer Arbeitsplatz</button>
    </div>

    <section class="karte">
        <div class="tabelle-scroll">
            <table>
                <thead>
                    <tr><th>Nummer</th><th>Bezeichnung</th><th>Kostenstelle</th><th class="zahl">Stundensatz</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($plaetze as $platz)
                        <tr wire:key="platz-{{ $platz->id }}" @class(['hervorgehoben' => $ausgewaehlt === $platz->id])>
                            <td><strong>{{ $platz->nummer }}</strong></td>
                            <td>{{ $platz->bezeichnung }}</td>
                            <td>{{ $platz->kostenstelle }}</td>
                            <td class="zahl">{{ euro($platz->stundensatz) }}/h</td>
                            <td><span class="badge {{ $platz->aktiv ? 'aktiv' : 'inaktiv' }}">{{ $platz->aktiv ? 'aktiv' : 'inaktiv' }}</span></td>
                            <td><button wire:click="bearbeiten({{ $platz->id }})" class="btn btn-klein btn-sekundaer">Bearbeiten</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <form wire:submit="speichern" class="karte">
        <h2>{{ $ausgewaehlt ? 'Arbeitsplatz '.$nummer.' bearbeiten' : 'Neuer Arbeitsplatz' }}</h2>
        <div class="formular-raster">
            <div class="feld">
                <label for="nummer">Nummer</label>
                <input id="nummer" wire:model="nummer" maxlength="10" placeholder="z. B. AP-60">
                @error('nummer')<small class="ueber">{{ $message }}</small>@enderror
            </div>
            <div class="feld">
                <label for="bezeichnung">Bezeichnung</label>
                <input id="bezeichnung" wire:model="bezeichnung" maxlength="80">
                @error('bezeichnung')<small class="ueber">{{ $message }}</small>@enderror
            </div>
            <div class="feld">
                <label for="kostenstelle">Kostenstelle</label>
                <input id="kostenstelle" wire:model="kostenstelle" maxlength="10">
                @error('kostenstelle')<small class="ueber">{{ $message }}</small>@enderror
            </div>
            <div class="feld">
                <label for="stundensatz">Stundensatz (€/h)</label>
                <input id="stundensatz" type="number" min="0" step="0.01" wire:model="stundensatz">
                @error('stundensatz')<small class="ueber">{{ $message }}</small>@enderror
            </div>
            <div class="feld">
                <label><input type="checkbox" wire:model="aktiv"> aktiv</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primaer">Speichern</button>
    </form>
</div>
