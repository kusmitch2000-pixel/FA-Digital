<?php

namespace Tests\Feature;

use App\Models\Benutzer;
use Database\Seeders\ArbeitsgangWerkerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ArbeitsgangWerkerSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_taetigkeitskonten_und_zuordnungen_fuer_offene_arbeitsgaenge(): void
    {
        $alleArten = [];
        foreach (config('arbeitsgang_werker') as $gruppe) {
            $werker = Benutzer::where('personalnummer', $gruppe['personalnummer'])->firstOrFail();
            $this->assertSame('werker', $werker->rolle);
            $this->assertTrue($werker->aktiv);
            $this->assertTrue(Hash::check('fa-digital', $werker->passwort_hash));
            $this->assertSame($gruppe['email'], $werker->email);
            $alleArten = array_merge($alleArten, $gruppe['arbeitsgaenge']);
            $ids = DB::table('arbeitsgang as ag')->join('fertigungsauftrag as fa', 'fa.id', '=', 'ag.fertigungsauftrag_id')
                ->where('ag.status', 'offen')->whereIn('fa.status', ['angelegt', 'freigegeben', 'in_arbeit'])
                ->whereIn('ag.bezeichnung', $gruppe['arbeitsgaenge'])->pluck('ag.id');
            foreach ($ids as $id) {
                $this->assertDatabaseHas('arbeitsgang_zuordnung', ['arbeitsgang_id' => $id, 'benutzer_id' => $werker->id]);
            }
        }
        $fehlendeArten = DB::table('arbeitsgang as ag')->join('fertigungsauftrag as fa', 'fa.id', '=', 'ag.fertigungsauftrag_id')
            ->where('ag.status', 'offen')->whereIn('fa.status', ['angelegt', 'freigegeben', 'in_arbeit'])
            ->whereNotIn('ag.bezeichnung', $alleArten)->pluck('ag.bezeichnung');
        $this->assertCount(0, $fehlendeArten);
    }

    public function test_wiederholtes_ausfuehren_bewahrt_passwort_und_vorhandene_zuordnungen(): void
    {
        $gruppe = config('arbeitsgang_werker')[0];
        $werker = Benutzer::where('personalnummer', $gruppe['personalnummer'])->firstOrFail();
        $werker->update(['passwort_hash' => 'IndividuellesPasswort123']);
        $id = DB::table('arbeitsgang as ag')->join('fertigungsauftrag as fa', 'fa.id', '=', 'ag.fertigungsauftrag_id')
            ->where('ag.status', 'offen')->whereIn('fa.status', ['angelegt', 'freigegeben', 'in_arbeit'])
            ->whereIn('ag.bezeichnung', $gruppe['arbeitsgaenge'])->value('ag.id');
        $this->assertNotNull($id);
        $bestehenderWerker = $this->benutzer('2004');
        DB::table('arbeitsgang_zuordnung')->insertOrIgnore(['arbeitsgang_id' => $id, 'benutzer_id' => $bestehenderWerker->id]);
        $this->seed(ArbeitsgangWerkerSeeder::class);
        $this->seed(ArbeitsgangWerkerSeeder::class);
        $this->assertTrue(Hash::check('IndividuellesPasswort123', $werker->fresh()->passwort_hash));
        $this->assertDatabaseHas('arbeitsgang_zuordnung', ['arbeitsgang_id' => $id, 'benutzer_id' => $bestehenderWerker->id]);
        $this->assertDatabaseCount('benutzer', 5 + count(config('arbeitsgang_werker')));
    }
}
