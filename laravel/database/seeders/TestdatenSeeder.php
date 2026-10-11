<?php

namespace Database\Seeders;

use App\Support\SqlSkript;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Lädt datenbank/02_testdaten.sql: dieselben Beispieldaten wie im Prototyp.
// Alle Zeitpunkte sind relativ zum Import, die Hallenansicht zeigt danach laufende Arbeit.
class TestdatenSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('benutzer')->exists()) {
            $this->command->info('Testdaten sind schon vorhanden. Neu laden mit: php artisan migrate:fresh --seed');

            return;
        }

        SqlSkript::ausfuehren('02_testdaten.sql');
    }
}
