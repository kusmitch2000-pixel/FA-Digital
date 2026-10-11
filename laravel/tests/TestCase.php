<?php

namespace Tests;

use App\Models\Benutzer;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Die Tests laufen gegen MariaDB (Datenbank fa_digital_test, siehe phpunit.xml), weil das Datenmodell
 * Sichten, CHECK-Regeln und ENUMs nutzt. Vor dem ersten Test baut RefreshDatabase die Datenbank aus
 * den SQL-Skripten in datenbank/ auf und lädt die Testdaten. Jeder Test läuft in einer Transaktion.
 */
abstract class TestCase extends BaseTestCase
{
    protected bool $seed = true;

    /** Test-Benutzer aus datenbank/02_testdaten.sql, z. B. 1002 = Lena Hoffmann (Meister) */
    protected function benutzer(string $personalnummer): Benutzer
    {
        return Benutzer::where('personalnummer', $personalnummer)->firstOrFail();
    }

    protected function alsBenutzer(string $personalnummer): Benutzer
    {
        $benutzer = $this->benutzer($personalnummer);
        $this->actingAs($benutzer);

        return $benutzer;
    }
}
