<div>
    <div class="seitenkopf">
        <div>
            <h1>Benutzer</h1>
            <p class="leise">Zugänge für Werkstatt und Büro. Die Rolle bestimmt, welche Seiten jemand sieht.</p>
        </div>
    </div>

    @error('aktion')<div class="hinweis fehler" role="alert">{{ $message }}</div>@enderror

    <section class="karte">
        <h2>Alle Benutzer</h2>
        <div class="tabelle-scroll">
            <table>
                <thead>
                    <tr><th>Name</th><th>Pers.-Nr.</th><th>E-Mail</th><th>Rolle</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($benutzer as $person)
                        <tr wire:key="benutzer-{{ $person->id }}">
                            <td><strong>{{ $person->name }}</strong></td>
                            <td>{{ $person->personalnummer }}</td>
                            <td>{{ $person->email }}</td>
                            <td>{{ $person->rollenbezeichnung }}</td>
                            <td><span class="badge {{ $person->aktiv ? 'aktiv' : 'inaktiv' }}">{{ $person->aktiv ? 'aktiv' : 'gesperrt' }}</span></td>
                            <td>
                                <div class="knopfleiste ohne-abstand">
                                    <button wire:click="$set('passwortFuer', {{ $person->id }})" class="btn btn-klein btn-sekundaer">Passwort</button>
                                    @unless ($person->is(auth()->user()))
                                        <button wire:click="aktivUmschalten({{ $person->id }})" class="btn btn-klein btn-sekundaer">{{ $person->aktiv ? 'Sperren' : 'Entsperren' }}</button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($passwortFuer)
            <form wire:submit="passwortSetzen" class="knopfleiste abstand-oben">
                <label for="neues-passwort">Neues Passwort für {{ $benutzer->firstWhere('id', $passwortFuer)?->name }}</label>
                <input id="neues-passwort" type="password" wire:model="neuesPasswort" autocomplete="new-password" class="schmal">
                <button type="submit" class="btn btn-primaer btn-klein">Speichern</button>
                <button type="button" wire:click="$set('passwortFuer', null)" class="btn btn-sekundaer btn-klein">Abbrechen</button>
                @error('neuesPasswort')<small class="ueber">{{ $message }}</small>@enderror
            </form>
        @endif
    </section>

    <div class="spalten-gleich">
        <form wire:submit="anlegen" class="karte">
            <h2>Neuen Benutzer anlegen</h2>
            <div class="formular-raster">
                <div class="feld">
                    <label for="vorname">Vorname</label>
                    <input id="vorname" wire:model="vorname" maxlength="50">
                    @error('vorname')<small class="ueber">{{ $message }}</small>@enderror
                </div>
                <div class="feld">
                    <label for="nachname">Nachname</label>
                    <input id="nachname" wire:model="nachname" maxlength="50">
                    @error('nachname')<small class="ueber">{{ $message }}</small>@enderror
                </div>
                <div class="feld">
                    <label for="personalnummer">Personalnummer</label>
                    <input id="personalnummer" wire:model="personalnummer" maxlength="10">
                    @error('personalnummer')<small class="ueber">{{ $message }}</small>@enderror
                </div>
                <div class="feld">
                    <label for="email">E-Mail</label>
                    <input id="email" type="email" wire:model="email" maxlength="100">
                    @error('email')<small class="ueber">{{ $message }}</small>@enderror
                </div>
                <div class="feld">
                    <label for="rolle">Rolle</label>
                    <select id="rolle" wire:model="rolle">
                        @foreach ($rollen as $wert => $bezeichnung)
                            <option value="{{ $wert }}">{{ $bezeichnung }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="feld">
                    <label for="passwort">Start-Passwort</label>
                    <input id="passwort" type="password" wire:model="passwort" autocomplete="new-password">
                    @error('passwort')<small class="ueber">{{ $message }}</small>@enderror
                </div>
            </div>
            <button type="submit" class="btn btn-primaer">Benutzer anlegen</button>
        </form>

        <section class="karte">
            <h2>Welche Rolle darf was?</h2>
            <div class="tabelle-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Bereich</th>
                            @foreach ($rollen as $wert => $bezeichnung)
                                <th class="mitte" title="{{ $bezeichnung }}">{{ ['arbeitsvorbereitung' => 'AV', 'controlling' => 'Contr.'][$wert] ?? $bezeichnung }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (config('fa.menue') as $recht => $seite)
                            <tr>
                                <td>{{ $seite }}</td>
                                @foreach ($rollen as $wert => $bezeichnung)
                                    <td class="mitte">{{ in_array($wert, config('fa.rechte')[$recht] ?? [], true) ? '✓' : '' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="leise klein">Eingestellt in <code>config/fa.php</code>.</p>
        </section>
    </div>
</div>
