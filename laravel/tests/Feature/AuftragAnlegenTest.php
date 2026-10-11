<?php

namespace Tests\Feature;

use App\Livewire\AuftragAnlegen;
use App\Livewire\AuftragDetail;
use App\Models\Artikel;
use App\Models\Fertigungsauftrag;
use App\Models\Pruefpunkt;
use App\Models\Zertifikatsart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AuftragAnlegenTest extends TestCase
{
    use RefreshDatabase;

    private function anlegen(array $zertifikate, bool $freigeben)
    {
        return Livewire::test(AuftragAnlegen::class)
            ->set('artikelId', Artikel::where('artikelnummer', 'W-200')->value('id'))
            ->set('menge', 60)
            ->set('liefertermin', now()->addWeeks(3)->toDateString())
            ->set('kunde', 'Nordwerk Maschinenbau GmbH')
            ->set('zertifikate', $zertifikate)
            ->call('speichern', $freigeben);
    }

    /** Rückmeldung vom 01.10.2026: Zertifikate bei der Anlage angeben, Prüfpunkte kommen zum Werker */
    public function test_werkszeugnis_bringt_zusaetzliche_pruefpunkte(): void
    {
        $anna = $this->alsBenutzer('1001');
        $zeugnis22 = (string) Zertifikatsart::where('kuerzel', '2.2')->value('id');

        $this->anlegen([$zeugnis22], true)->assertHasNoErrors()->assertRedirect(route('auftrag', 'FA-1007'));

        $auftrag = Fertigungsauftrag::with('arbeitsgaenge', 'zertifikate')->where('auftragsnummer', 'FA-1007')->firstOrFail();
        $this->assertSame('freigegeben', $auftrag->status);
        $this->assertSame($anna->id, $auftrag->freigegeben_von);
        $this->assertSame([10, 20, 30], $auftrag->arbeitsgaenge->pluck('ag_nummer')->all());
        $this->assertSame('gefordert', $auftrag->zertifikate->first()->status);

        // Soll-Zeit AG 20 Drehen: 30 min Rüsten + 60 Stück × 4 min, Stundensatz als Kopie
        $drehen = DB::table('v_arbeitsgang_zeit')->where('arbeitsgang_id', $auftrag->arbeitsgaenge[1]->id)->first();
        $this->assertEquals(270, $drehen->soll_zeit_min);
        $this->assertEquals(85, $drehen->stundensatz);

        // 3 Standardprüfungen plus 2 Prüfungen wegen Werkszeugnis 2.2 (wie Ablauf 1 in 05_beispielablaeufe.sql)
        $this->assertSame(5, Pruefpunkt::whereIn('arbeitsgang_id', $auftrag->arbeitsgaenge->pluck('id'))->count());
    }

    public function test_ohne_zertifikat_nur_standardpruefungen_und_erst_angelegt(): void
    {
        $this->alsBenutzer('1001');

        $this->anlegen([], false)->assertHasNoErrors();

        $auftrag = Fertigungsauftrag::where('auftragsnummer', 'FA-1007')->firstOrFail();
        $this->assertSame('angelegt', $auftrag->status);
        $this->assertSame(3, Pruefpunkt::whereIn('arbeitsgang_id', $auftrag->arbeitsgaenge()->pluck('id'))->count());

        // Freigeben im Auftragsdetail, danach steht AG 10 im Arbeitsvorrat
        Livewire::test(AuftragDetail::class, ['auftrag' => $auftrag])->call('freigeben')->assertHasNoErrors();
        $this->assertSame('freigegeben', $auftrag->fresh()->status);
        $this->assertSame('bereit', DB::table('v_arbeitsvorrat')->where('auftragsnummer', 'FA-1007')->where('ag_nummer', 10)->value('bereitschaft'));
    }

    public function test_eingaben_werden_geprueft(): void
    {
        $this->alsBenutzer('1001');

        Livewire::test(AuftragAnlegen::class)
            ->set('menge', 0)
            ->set('liefertermin', now()->subDay()->toDateString())
            ->call('speichern')
            ->assertHasErrors(['menge' => 'min', 'liefertermin' => 'after_or_equal']);

        $this->assertFalse(Fertigungsauftrag::where('auftragsnummer', 'FA-1007')->exists());
    }

    public function test_stornieren_nur_vor_dem_ersten_start(): void
    {
        $this->alsBenutzer('1001');

        // FA-1006 ist angelegt, an FA-1002 wird schon gearbeitet
        $fa1006 = Fertigungsauftrag::where('auftragsnummer', 'FA-1006')->first();
        Livewire::test(AuftragDetail::class, ['auftrag' => $fa1006])->call('stornieren')->assertHasNoErrors();
        $this->assertSame('storniert', $fa1006->fresh()->status);

        $fa1002 = Fertigungsauftrag::where('auftragsnummer', 'FA-1002')->first();
        Livewire::test(AuftragDetail::class, ['auftrag' => $fa1002])->call('stornieren')->assertHasErrors('aktion');
        $this->assertSame('in_arbeit', $fa1002->fresh()->status);
    }
}
