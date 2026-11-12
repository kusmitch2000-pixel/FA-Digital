<?php

use App\Support\SqlSkript;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE fertigungsauftrag MODIFY status ENUM('angelegt','freigegeben','in_arbeit','messen','fertig','nachkalkuliert','storniert') NOT NULL DEFAULT 'angelegt'");
        DB::statement("ALTER TABLE arbeitsgang MODIFY status ENUM('offen','laeuft','unterbrochen','messen','erledigt') NOT NULL DEFAULT 'offen'");
        SqlSkript::ausfuehren('03_sichten.sql');
    }

    public function down(): void
    {
        throw new RuntimeException('Eine Rücknahme würde Messdaten gefährden. Migration und bestehende Aufträge zuerst manuell prüfen.');
    }
};
