<?php

use App\Support\SqlSkript;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Die 14 Fachtabellen kommen unverändert aus datenbank/01_schema.sql
return new class extends Migration
{
    public function up(): void
    {
        // Wurden die SQL-Skripte schon in phpMyAdmin importiert, übernimmt Laravel die Tabellen
        // und legt nur seine eigenen technischen Tabellen (sessions, cache, jobs) dazu.
        if (Schema::hasTable('benutzer')) {
            return;
        }

        // Wie CREATE DATABASE im Skript: deutsche Sortierung für alle neuen Tabellen
        DB::statement('ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_german2_ci');

        SqlSkript::ausfuehren('01_schema.sql');
    }

    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            foreach (array_reverse(SqlSkript::tabellen('01_schema.sql')) as $tabelle) {
                Schema::dropIfExists($tabelle);
            }
        });
    }
};
