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

            <h3>Administration</h3>
            <dl class="testkonto"><dt>Admin · Sven Petersen</dt><dd>E-Mail: <strong>sven.petersen@fa-digital.de</strong><br>Passwort: <strong>fa-digital</strong></dd></dl>

            <h3>Arbeitsvorbereitung</h3>
            <dl class="testkonto"><dt>Anna Becker</dt><dd>E-Mail: <strong>anna.becker@fa-digital.de</strong><br>Passwort: <strong>fa-digital</strong></dd></dl>

            <h3>Meister</h3>
            <dl class="testkonto"><dt>Lena Hoffmann</dt><dd>E-Mail: <strong>lena.hoffmann@fa-digital.de</strong><br>Passwort: <strong>fa-digital</strong></dd></dl>

            <h3>Controlling</h3>
            <dl class="testkonto"><dt>Tobias Krüger</dt><dd>E-Mail: <strong>tobias.krueger@fa-digital.de</strong><br>Passwort: <strong>fa-digital</strong></dd></dl>

            <h3>Werker</h3>
            <dl class="testkonto"><dt>Alex Beispiel · Auftrag ohne Zertifikat</dt><dd>E-Mail: <strong>demo.werker@fa-digital.local</strong><br>Passwort: <strong>WerkerDemo123!</strong></dd></dl>
            <dl class="testkonto"><dt>Mia Messung · Auftrag mit Werkszertifikat</dt><dd>E-Mail: <strong>demo.messung@fa-digital.local</strong><br>Passwort: <strong>MessungDemo123!</strong></dd></dl>
            <dl class="testkonto"><dt>Jonas Wolff</dt><dd>E-Mail: <strong>jonas.wolff@fa-digital.de</strong><br>Passwort: <strong>fa-digital</strong></dd></dl>
            <dl class="testkonto"><dt>Mehmet Yilmaz</dt><dd>E-Mail: <strong>mehmet.yilmaz@fa-digital.de</strong><br>Passwort: <strong>fa-digital</strong></dd></dl>
            <dl class="testkonto"><dt>Paul Schneider</dt><dd>E-Mail: <strong>paul.schneider@fa-digital.de</strong><br>Passwort: <strong>fa-digital</strong></dd></dl>
            <p class="klein leise">Die beiden Demo-Werker stehen nur zur Verfügung, wenn ihre optionalen Seeder ausgeführt wurden. Katrin Wagner ist in den ursprünglichen Testdaten gesperrt und kann sich nicht anmelden.</p>
        </section>
    @endif
</main>
</body>
</html>
