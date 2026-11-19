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
        <section class="testkonten" aria-labelledby="testkonten-titel">
            <h2 id="testkonten-titel">Anmeldedaten für die Entwicklung</h2>
            <p class="klein leise">Beispielkonten für die lokale Testumgebung. Als Benutzername dient die E-Mail-Adresse. Die Angaben gelten für die ursprünglichen Testdaten; geänderte Passwörter und selbst angelegte Konten werden hier nicht angezeigt.</p>
            <p class="klein leise">Zwei Rollen in normalen Browserfenstern testen: Im ersten Fenster <a href="http://localhost:{{ request()->getPort() }}">localhost:{{ request()->getPort() }}</a> verwenden, im zweiten <a href="http://127.0.0.1:{{ request()->getPort() }}">127.0.0.1:{{ request()->getPort() }}</a>. Beide Adressen nutzen dieselben lokalen Daten. Je Adresse getrennt anmelden.</p>

            <h3>Administration</h3>
            <dl class="testkonto"><dt>Admin · Sven Petersen</dt><dd>E-Mail: <strong>sven.petersen@fa-digital.de</strong><br>Passwort: <strong>fa-digital</strong></dd></dl>

            <h3>Arbeitsvorbereitung</h3>
            <dl class="testkonto"><dt>Anna Becker</dt><dd>E-Mail: <strong>anna.becker@fa-digital.de</strong><br>Passwort: <strong>fa-digital</strong></dd></dl>

            <h3>Meister</h3>
            <dl class="testkonto"><dt>Lena Hoffmann</dt><dd>E-Mail: <strong>lena.hoffmann@fa-digital.de</strong><br>Passwort: <strong>fa-digital</strong></dd></dl>

            <h3>Controlling</h3>
            <dl class="testkonto"><dt>Tobias Krüger</dt><dd>E-Mail: <strong>tobias.krueger@fa-digital.de</strong><br>Passwort: <strong>fa-digital</strong></dd></dl>

            <h3>Werker</h3>
            <h3>Werker nach Tätigkeit</h3>
            <p class="klein leise">Diese lokalen Tätigkeitskonten stehen nach dem Ausführen des Standard-Seeders zur Verfügung. Das anfängliche Passwort ist jeweils <strong>fa-digital</strong>.</p>
            @foreach (config('arbeitsgang_werker') as $gruppe)
                <dl class="testkonto"><dt>{{ $gruppe['taetigkeit'] }}</dt><dd>E-Mail: <strong>{{ $gruppe['email'] }}</strong><br>Passwort: <strong>fa-digital</strong></dd></dl>
            @endforeach
            <p class="klein leise">Katrin Wagner ist in den ursprünglichen Testdaten gesperrt und kann sich nicht anmelden.</p>
        </section>
    @endif
</main>
</body>
</html>
