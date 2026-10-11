<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'FA-Digital' }} – FA-Digital</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/production.css') }}">
    @livewireStyles
</head>
<body>
    @php($benutzer = auth()->user())
    <header class="kopf">
        <a class="logo" href="{{ route('home') }}">FA-Digital</a>
        <span class="untertitel">Digitaler Fertigungsauftrag</span>
        <div class="benutzer">
            <span>{{ $benutzer->name }} · {{ $benutzer->rollenbezeichnung }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf <button class="btn btn-sekundaer btn-klein" type="submit">Abmelden</button></form>
        </div>
    </header>
    <div class="rahmen">
        {{-- Jede Rolle sieht nur ihre Bereiche (config/fa.php) --}}
        <nav class="menue" aria-label="Hauptnavigation">
            @foreach (config('fa.menue') as $route => $bezeichnung)
                @if ($benutzer->darf($route))
                    <a href="{{ route($route) }}" @class(['aktiv' => request()->routeIs($route) || ($route === 'auftraege' && request()->routeIs('auftrag'))])>{{ $bezeichnung }}</a>
                @endif
            @endforeach
        </nav>
        <main class="inhalt">
            @if (session('status'))
                <div class="hinweis" role="status">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>
    @livewireScripts
</body>
</html>
