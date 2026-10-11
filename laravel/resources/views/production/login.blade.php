<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Anmelden – FA-Digital</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/production.css') }}">
</head>
<body class="login-seite">
<main class="login-box">
    <h1>FA-Digital</h1>
    <p class="leise">Digitaler Fertigungsauftrag mit Zeiterfassung und Nachkalkulation</p>
    @if (session('status'))<div class="hinweis">{{ session('status') }}</div>@endif
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="feld">
            <label for="email">E-Mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            @error('email')<small class="ueber">{{ $message }}</small>@enderror
        </div>
        <div class="feld">
            <label for="password">Passwort</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
            @error('password')<small class="ueber">{{ $message }}</small>@enderror
        </div>
        <button class="btn btn-primaer btn-breit" type="submit">Anmelden</button>
    </form>
    @if (app()->environment('local'))
        {{-- Nur in der Entwicklung: Konten aus datenbank/02_testdaten.sql, Passwort steht in laravel/README.md --}}
        <details class="klein testkonten">
            <summary>Test-Benutzer</summary>
            <ul>
                <li>sven.petersen@fa-digital.de · Admin</li>
                <li>anna.becker@fa-digital.de · Arbeitsvorbereitung</li>
                <li>lena.hoffmann@fa-digital.de · Meister</li>
                <li>tobias.krueger@fa-digital.de · Controlling</li>
                <li>jonas.wolff@fa-digital.de · Werker</li>
            </ul>
        </details>
    @endif
</main>
</body>
</html>
