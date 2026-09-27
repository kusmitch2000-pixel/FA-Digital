<div>
    <div class="seitenkopf"><div><h1>Neuer Fertigungsauftrag</h1><p class="leise">Arbeitsgänge und Soll-Kosten werden aus dem Arbeitsplan berechnet.</p></div></div>
    <form wire:submit="save">
        <section class="karte"><h2>Auftragsdaten</h2><div class="formular-raster">
            <div class="feld"><label for="artikel">Artikel</label><select id="artikel" wire:model.live="articleId"><option value="">Bitte wählen</option>@foreach ($articles as $article)<option value="{{ $article->id }}">{{ $article->code }} {{ $article->name }}</option>@endforeach</select>@error('articleId')<small class="ueber">{{ $message }}</small>@enderror</div>
            <div class="feld"><label for="menge">Menge (Stück)</label><input id="menge" type="number" min="1" wire:model.live="quantity">@error('quantity')<small class="ueber">{{ $message }}</small>@enderror</div>
            <div class="feld"><label for="termin">Liefertermin</label><input id="termin" type="date" wire:model="dueDate">@error('dueDate')<small class="ueber">{{ $message }}</small>@enderror</div>
            <div class="feld"><label for="kunde">Kunde oder Kommission</label><input id="kunde" wire:model="customer"></div>
        </div><div class="feld"><label for="bemerkung">Bemerkung</label><textarea id="bemerkung" wire:model="notes"></textarea></div></section>
        <section class="karte"><h2>Vorschau: Arbeitsgänge und Soll-Kosten</h2><div class="tabelle-scroll"><table><thead><tr><th>AG</th><th>Arbeitsgang</th><th>Arbeitsplatz</th><th class="zahl">Rüstzeit</th><th class="zahl">Stückzeit</th><th class="zahl">Soll-Zeit</th><th class="zahl">Stundensatz</th><th class="zahl">Soll-Kosten</th></tr></thead><tbody>
            @php $totalMinutes = 0; $totalCost = 0; @endphp
            @foreach ($selected?->steps ?? [] as $step)
                @php $minutes = (float) $step->setup_minutes + $quantity * (float) $step->minutes_per_unit; $cost = $minutes / 60 * (float) $step->workstation->hourly_rate; $totalMinutes += $minutes; $totalCost += $cost; @endphp
                <tr><td>{{ $step->sequence }}</td><td>{{ $step->name }}</td><td>{{ $step->workstation->name }}</td><td class="zahl">{{ number_format($step->setup_minutes, 1, ',', '.') }} min</td><td class="zahl">{{ number_format($step->minutes_per_unit, 1, ',', '.') }} min</td><td class="zahl">{{ number_format($minutes, 1, ',', '.') }} min</td><td class="zahl">{{ number_format($step->workstation->hourly_rate, 2, ',', '.') }} €</td><td class="zahl">{{ number_format($cost, 2, ',', '.') }} €</td></tr>
            @endforeach
        </tbody><tfoot><tr><td colspan="5">Summe</td><td class="zahl">{{ number_format($totalMinutes, 1, ',', '.') }} min</td><td></td><td class="zahl">{{ number_format($totalCost, 2, ',', '.') }} €</td></tr></tfoot></table></div></section>
        <div class="knopfleiste"><button type="submit" class="btn btn-sekundaer">Speichern</button><button type="button" wire:click="save(true)" class="btn btn-primaer">Speichern und freigeben</button><a class="btn btn-sekundaer" href="{{ route('orders.index') }}">Abbrechen</a></div>
    </form>
</div>
