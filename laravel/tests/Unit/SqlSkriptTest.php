<?php

namespace Tests\Unit;

use App\Support\SqlSkript;
use PHPUnit\Framework\TestCase;

class SqlSkriptTest extends TestCase
{
    public function test_semikolon_trennt_nur_ausserhalb_von_text_und_kommentaren(): void
    {
        $skript = <<<'SQL'
            -- Kommentar mit ; Semikolon
            SET @hash = '$2y$10$abc;def';
            INSERT INTO t (a, b) VALUES ('It''s; ok', "x;y"); # noch ein Kommentar ;
            /* Block; Kommentar */ SELECT 1
            SQL;

        $this->assertSame([
            "SET @hash = '\$2y\$10\$abc;def'",
            "INSERT INTO t (a, b) VALUES ('It''s; ok', \"x;y\")",
            'SELECT 1',
        ], SqlSkript::anweisungen($skript));
    }

    public function test_zwei_bindestriche_ohne_leerzeichen_sind_kein_kommentar(): void
    {
        $this->assertSame(['SELECT 5--3'], SqlSkript::anweisungen("SELECT 5--3;\n"));
    }
}
