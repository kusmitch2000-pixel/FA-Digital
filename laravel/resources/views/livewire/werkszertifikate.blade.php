<div>
    <div class="seitenkopf">
        <div>
            <h1>Werkszertifikate</h1>
            <p class="leise">Werkszeugnisse nach DIN EN 10204. Die Messwerte kommen aus den Checklisten der Werker.</p>
        </div>
    </div>

    @error('aktion')<div class="hinweis fehler" role="alert">{{ $message }}</div>@enderror

    <section class="karte">
        <div class="tabelle-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Auftrag</th><th>Artikel</th><th>Kunde</th><th>Zertifikat</th><th>Auftragsstatus</th>
                        <th>Prüfpunkte</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($zertifikate as $zertifikat)
                        <tr wire:key="zertifikat-{{ $zertifikat->auftrag_zertifikat_id }}">
                            <td><a href="{{ route('auftrag', $zertifikat->auftragsnummer) }}"><strong>{{ $zertifikat->auftragsnummer }}</strong></a></td>
                            <td>{{ $zertifikat->artikelnummer }} {{ $zertifikat->artikel }}<br><span class="leise klein">{{ $zertifikat->menge }} Stück</span></td>
                            <td>{{ $zertifikat->kunde ?? '–' }}</td>
                            <td><strong>{{ $zertifikat->kuerzel }}</strong> {{ $zertifikat->zertifikat }}</td>
                            <td><span class="badge {{ status_klasse($zertifikat->auftragsstatus) }}">{{ status_text($zertifikat->auftragsstatus) }}</span></td>
                            <td>
                                {{ $zertifikat->pruefpunkte - $zertifikat->offen - $zertifikat->nicht_in_ordnung }} von {{ $zertifikat->pruefpunkte }} i. O.
                                @if ($zertifikat->nicht_in_ordnung > 0)<br><span class="badge verzug">{{ $zertifikat->nicht_in_ordnung }} n. i. O.</span>@endif
                                @if ($zertifikat->offen > 0)<br><span class="leise klein">{{ $zertifikat->offen }} offen</span>@endif
                            </td>
                            <td>
                                <span class="badge {{ status_klasse($zertifikat->status) }}">{{ $zertifikat->status }}</span>
                                @if ($zertifikat->freigegeben_am)<br><span class="leise klein">{{ zeitpunkt($zertifikat->freigegeben_am) }}</span>@endif
                            </td>
                            <td>
                                <div class="knopfleiste ohne-abstand">
                                    <a class="btn btn-klein btn-sekundaer" href="{{ route('zertifikat.druck', $zertifikat->auftrag_zertifikat_id) }}" target="_blank">Ansehen</a>
                                    @if ($darfFreigeben && $zertifikat->status !== 'freigegeben')
                                        <button wire:click="freigeben({{ $zertifikat->auftrag_zertifikat_id }})" class="btn btn-klein btn-primaer"
                                                @disabled(! $zertifikat->bereit_zur_freigabe) title="{{ $zertifikat->bereit_zur_freigabe ? 'Werkszeugnis freigeben' : 'Erst möglich, wenn der Auftrag fertig ist und alle Prüfpunkte in Ordnung sind' }}">Freigeben</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">Kein Auftrag mit gefordertem Werkszertifikat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
