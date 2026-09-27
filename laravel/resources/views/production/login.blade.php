<!DOCTYPE html>
<html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Anmelden – FA-Digital</title><link rel="stylesheet" href="{{ asset('css/production.css') }}"></head>
<body class="login-seite"><main class="login-box"><h1>FA-Digital</h1><p class="leise">Digitaler Fertigungsauftrag mit Zeiterfassung und Nachkalkulation</p>
@if(session('status'))<div class="hinweis">{{ session('status') }}</div>@endif
<form method="POST" action="{{ route('login.store') }}">@csrf
<div class="feld"><label for="email">E-Mail</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>@error('email')<small class="ueber">{{ $message }}</small>@enderror</div>
<div class="feld"><label for="password">Passwort</label><input id="password" type="password" name="password" autocomplete="current-password" required>@error('password')<small class="ueber">{{ $message }}</small>@enderror</div>
<div class="feld"><label><input type="checkbox" name="remember"> Angemeldet bleiben</label></div><button class="btn btn-primaer btn-breit" type="submit">Anmelden</button></form>
@if(Route::has('password.request'))<p class="klein" style="margin-top:1rem"><a href="{{ route('password.request') }}">Passwort vergessen?</a></p>@endif
</main></body></html>
