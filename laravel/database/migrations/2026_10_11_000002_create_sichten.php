<?php

use App\Support\SqlSkript;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Die 14 Sichten aus datenbank/03_sichten.sql. Sie rechnen Soll-/Ist-Zeiten, Kosten,
// Ampeln und Vorschläge. Die Anwendung liest diese Werte nur und rechnet sie nicht nach.
return new class extends Migration
{
    public function up(): void
    {
        SqlSkript::ausfuehren('03_sichten.sql');
    }

    public function down(): void
    {
        foreach (array_reverse(SqlSkript::sichten('03_sichten.sql')) as $sicht) {
            DB::statement("DROP VIEW IF EXISTS {$sicht}");
        }
    }
};
