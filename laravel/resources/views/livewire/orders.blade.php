<div>
    <div class="seitenkopf"><div><h1>Aufträge</h1><p class="leise">Fertigungsaufträge und Fortschritt</p></div>@if(auth()->user()->role === 'admin')<a class="btn btn-primaer" href="{{ route('orders.create') }}">+ Neuer Auftrag</a>@endif</div>
    <div class="karte formular-raster">
        <div class="feld"><label for="suche">Suche</label><input id="suche" type="search" wire:model.live.debounce.300ms="search" placeholder="Auftrag oder Artikel"></div>
        <div class="feld"><label for="status">Status</label><select id="status" wire:model.live="status"><option value="">alle</option><option value="angelegt">angelegt</option><option value="freigegeben">freigegeben</option><option value="in_arbeit">in Arbeit</option><option value="fertig">fertig</option><option value="storniert">storniert</option></select></div>
    </div>
    <section class="karte"><div class="tabelle-scroll"><table><thead><tr><th>Auftrag</th><th>Artikel</th><th class="zahl">Menge</th><th>Liefertermin</th><th>Status</th><th>Fortschritt</th><th class="zahl">Soll-Zeit</th><th class="zahl">Ist-Zeit</th><th></th></tr></thead><tbody>
        @forelse ($orders as $order)
            @php $total = $order->operations->count(); $done = $order->operations->where('status', 'fertig')->count(); @endphp
            <tr><td><a href="{{ route('orders.show', $order) }}"><strong>{{ $order->number }}</strong></a></td><td>{{ $order->article->code }} {{ $order->article->name }}</td><td class="zahl">{{ $order->quantity }}</td><td>{{ $order->due_date->format('d.m.Y') }}</td><td><span class="badge {{ $order->status === 'in_arbeit' ? 'in-arbeit' : $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span></td><td><div class="balken"><span style="width: {{ $total ? round($done / $total * 100) : 0 }}%"></span></div><small>{{ $done }} von {{ $total }} AG</small></td><td class="zahl">{{ number_format($order->operations->sum(fn ($op) => $op->planned_minutes), 0, ',', '.') }} min</td><td class="zahl">{{ number_format($order->operations->sum(fn ($op) => $op->actual_minutes), 0, ',', '.') }} min</td><td><a class="btn btn-klein btn-sekundaer" href="{{ route('orders.show', $order) }}">Details</a></td></tr>
        @empty <tr><td colspan="9">Keine Aufträge gefunden.</td></tr> @endforelse
    </tbody></table></div></section>
</div>
