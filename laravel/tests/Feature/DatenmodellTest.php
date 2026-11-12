<?php

namespace Tests\Feature;

use App\Support\SqlSkript;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Die Migrationen bauen genau das abgegebene Datenmodell aus datenbank/ auf */
class DatenmodellTest extends TestCase
{
    use RefreshDatabase;

    public function test_alle_tabellen_und_sichten_aus_den_sql_skripten_sind_da(): void
    {
        $tabellen = SqlSkript::tabellen('01_schema.sql');
        $sichten = SqlSkript::sichten('03_sichten.sql');

        $this->assertCount(14, $tabellen);
        $this->assertCount(14, $sichten);

        foreach ($tabellen as $tabelle) {
            $this->assertTrue(Schema::hasTable($tabelle), "Tabelle {$tabelle} fehlt");
        }

        $vorhanden = collect(DB::select('SELECT TABLE_NAME AS name FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE()'))->pluck('name');
        $this->assertEqualsCanonicalizing($sichten, $vorhanden->all());
    }

    public function test_nachkalkulation_rechnet_wie_im_prototyp(): void
    {
        $fa1001 = DB::table('v_nachkalkulation_auftrag')->where('auftragsnummer', 'FA-1001')->first();

        $this->assertEquals(755.00, $fa1001->soll_kosten);
        $this->assertEquals(799.67, $fa1001->ist_kosten);
        $this->assertEquals(5.9, $fa1001->abweichung_prozent);
        $this->assertSame('gelb', $fa1001->ampel);
        $this->assertEquals(15.10, $fa1001->soll_kosten_je_stueck);
        $this->assertEquals(16.66, $fa1001->ist_kosten_je_gutstueck);
    }

    public function test_sollzeit_vorschlag_ab_zehn_prozent_abweichung(): void
    {
        $kanten = DB::table('v_sollzeit_vorschlag')->where('artikelnummer', 'V-410')->where('ag_nummer', 20)->first();

        $this->assertEquals(20, $kanten->soll_ruestzeit_min);
        $this->assertEquals(29, $kanten->vorschlag_ruestzeit_min);
        $this->assertNull($kanten->vorschlag_stueckzeit_min);
    }
}
