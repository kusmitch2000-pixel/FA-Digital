<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'FA-Digital' }} – FA-Digital</title>
    <link rel="stylesheet" href="{{ asset('css/production.css') }}">
    @livewireStyles
</head>
<body>
    <header class="kopf">
        <a class="logo" href="{{ route('dashboard') }}">FA-Digital</a>
        <span class="untertitel">Digitaler Fertigungsauftrag</span>
        <div style="margin-left:auto; display:flex; align-items:center; gap:1rem">
            <span>{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf <button class="btn btn-sekundaer btn-klein" type="submit">Abmelden</button></form>
        </div>
    </header>
    <div class="rahmen">
        <nav class="menue" aria-label="Hauptnavigation">
            <a href="{{ route('dashboard') }}" @class(['aktiv' => request()->routeIs('dashboard')])>Hallenansicht</a>
            <a href="{{ route('orders.index') }}" @class(['aktiv' => request()->routeIs('orders.index', 'orders.show')])>Aufträge</a>
            @if(auth()->user()->role === 'admin')<a href="{{ route('orders.create') }}" @class(['aktiv' => request()->routeIs('orders.create')])>Auftrag anlegen</a>@endif
            <a href="{{ route('articles.index') }}" @class(['aktiv' => request()->routeIs('articles.index')])>Artikel &amp; Arbeitspläne</a>
            <a href="{{ route('workstations.index') }}" @class(['aktiv' => request()->routeIs('workstations.index')])>Arbeitsplätze</a>
            <a href="{{ route('terminal') }}" @class(['aktiv' => request()->routeIs('terminal')])>Werker-Terminal</a>
            <a href="{{ route('costing') }}" @class(['aktiv' => request()->routeIs('costing')])>Nachkalkulation</a>
            <a href="{{ route('reports') }}" @class(['aktiv' => request()->routeIs('reports')])>Auswertung</a>
            @if(auth()->user()->role === 'admin')<a href="{{ route('users.index') }}" @class(['aktiv' => request()->routeIs('users.index')])>Benutzer</a>@endif
        </nav>
        <main class="inhalt">
            @if (session('status')) <div class="hinweis">{{ session('status') }}</div> @endif
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
    @livewireScripts
</body>
</html>
