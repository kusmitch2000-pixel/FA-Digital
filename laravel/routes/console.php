<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('fa:admin {email}', function (string $email) {
    $name = $this->ask('Name');
    $password = $this->secret('Passwort (mindestens 12 Zeichen)');
    if (strlen($password ?? '') < 12) {
        $this->error('Das Passwort muss mindestens 12 Zeichen lang sein.');
        return 1;
    }
    \App\Models\User::updateOrCreate(['email' => $email], [
        'name' => $name,
        'password' => $password,
        'role' => 'admin',
        'email_verified_at' => now(),
    ]);
    $this->info('Administrator gespeichert.');
})->purpose('Create or update an administrator account');
