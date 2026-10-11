<?php

use App\Models\Benutzer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Erstes Admin-Konto ohne Testdaten anlegen, z. B. php artisan fa:admin chef@firma.de
Artisan::command('fa:admin {email}', function (string $email) {
    $vorname = $this->ask('Vorname');
    $nachname = $this->ask('Nachname');
    $personalnummer = $this->ask('Personalnummer');
    $password = $this->secret('Passwort (mindestens 12 Zeichen)');

    if (strlen($password ?? '') < 12) {
        $this->error('Das Passwort muss mindestens 12 Zeichen lang sein.');

        return 1;
    }

    Benutzer::updateOrCreate(['email' => $email], [
        'vorname' => $vorname,
        'nachname' => $nachname,
        'personalnummer' => $personalnummer,
        'passwort_hash' => $password,
        'rolle' => 'admin',
        'aktiv' => true,
    ]);

    $this->info('Administrator gespeichert.');
})->purpose('Create or update an administrator account');
