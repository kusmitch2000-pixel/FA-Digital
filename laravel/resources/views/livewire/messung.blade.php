<div>
    <p><a href="{{ route('terminal') }}">← Zurück zum Werker-Terminal</a></p>
    <div class="seitenkopf">
        <div>
            <h1>Messung für das Werkszertifikat</h1>
            <p class="leise">{{ $arbeitsgang->auftrag->auftragsnummer }} · AG {{ $arbeitsgang->ag_nummer }} {{ $arbeitsgang->bezeichnung }} · {{ $arbeitsgang->auftrag->artikel->bezeichnung }}</p>
            <span class="badge angelegt">Messen</span>
        </div>
    </div>
    <section class="karte">
        <p>Erfassen und speichern Sie alle Prüfpunkte. Der Auftrag bleibt bis zum Abschluss der Messung offen. Abweichungen werden dokumentiert; die Freigabe des Werkszertifikats erfolgt anschließend durch den Meister.</p>
        @error('aktion')<div class="hinweis fehler" role="alert">{{ $message }}</div>@enderror
        @if ($arbeitsgang->pruefpunkte->isEmpty())
            <p class="hinweis fehler">Für diesen Arbeitsgang sind keine Prüfpunkte hinterlegt. Bitte wenden Sie sich an die Arbeitsvorbereitung.</p>
        @else
            <div class="tabelle-scroll"><table>
                <thead><tr><th>Prüfpunkt</th><th>Soll / Toleranz</th><th>Ist</th><th>Bemerkung</th><th>Ergebnis</th><th>Aktion</th></tr></thead>
                <tbody>
                @foreach ($arbeitsgang->pruefpunkte as $punkt)
                    <tr wire:key="messung-{{ $punkt->id }}">
                        <td>{{ $punkt->bezeichnung }}</td>
                        <td>@if ($punkt->art === 'messwert'){{ zahl($punkt->untere_grenze, 3) }} bis {{ zahl($punkt->obere_grenze, 3) }} {{ $punkt->einheit }}@else Sichtprüfung @endif</td>
                        <td>
                            @if ($punkt->art === 'messwert')
                                <input type="text" inputmode="decimal" class="eingabe-kurz" wire:model="istwert.{{ $punkt->id }}" placeholder="{{ $punkt->istwert !== null ? zahl($punkt->istwert, 3) : 'Messwert' }}" aria-label="Messwert {{ $punkt->bezeichnung }}">
                            @endif
                            @error('istwert.'.$punkt->id)<small class="ueber">{{ $message }}</small>@enderror
                        </td>
                        <td><input type="text" wire:model="bemerkung.{{ $punkt->id }}" maxlength="255" placeholder="{{ $punkt->bemerkung ?? 'optional' }}" aria-label="Bemerkung {{ $punkt->bezeichnung }}"></td>
                        <td><span class="badge {{ status_klasse($punkt->ergebnis) }}">{{ status_text($punkt->ergebnis) }}</span>@if ($punkt->istwert !== null)<br>{{ zahl($punkt->istwert, 3) }} {{ $punkt->einheit }}@endif</td>
                        <td>
                            @if ($punkt->art === 'messwert')
                                <button class="btn btn-klein btn-primaer" wire:click="speichern({{ $punkt->id }})" wire:loading.attr="disabled">Speichern</button>
                            @else
                                <button class="btn btn-klein btn-erfolg" wire:click="speichern({{ $punkt->id }}, 'in_ordnung')" wire:loading.attr="disabled">i. O.</button>
                                <button class="btn btn-klein btn-gefahr" wire:click="speichern({{ $punkt->id }}, 'nicht_in_ordnung')" wire:loading.attr="disabled">n. i. O.</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
        <button class="btn btn-erfolg btn-gross" wire:click="abschliessen" wire:loading.attr="disabled">Messung abschließen</button>
    </section>
</div>
