<?php

namespace Tests\Feature;

use App\Livewire\MeisterStartseite;
use App\Livewire\Werkszertifikate;
use App\Models\AuftragZertifikat;
use App\Models\Fertigungsauftrag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class MeisterStartseiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_meister_navigiert_ueber_drei_hauptseiten_und_behaelt_auftragsdetails(): void
    {
        $this->alsBenutzer('1002');
        $antwort = $this->get('/meister')->assertOk()->assertSee('Mein Arbeitsaufkommen');
        $this->assertSame(1, preg_match('/<nav class="menue".*?<\/nav>/s', $antwort->getContent(), $treffer));
        $menue = $treffer[0];
        $this->assertSame(3, substr_count($menue, '<a '));
        foreach (['Startseite', 'Hallenansicht', 'Zertifikatsfreigaben'] as $punkt) {
            $this->assertStringContainsString($punkt, $menue);
        }
        foreach (['Arbeitsauftragsvorlauf', 'Aufträge', 'Nachkalkulation', 'Auswertung'] as $punkt) {
            $this->assertStringNotContainsString($punkt, $menue);
        }
        $this->get('/halle')->assertOk();
        $this->get('/zertifikate')->assertOk();
        $this->get('/auftraege/FA-1002')->assertOk();

        $this->alsBenutzer('2004');
        $this->get('/meister')->assertForbidden();
        Livewire::test(MeisterStartseite::class)->assertForbidden();
    }

    public function test_ueberfaellige_offene_auftraege_aber_keine_heutigen_oder_abgeschlossenen(): void
    {
        $this->alsBenutzer('1002');
        DB::table('fertigungsauftrag')->update(['liefertermin' => now()->addDays(2)->toDateString()]);
        DB::table('fertigungsauftrag')->where('auftragsnummer', 'FA-1002')->update(['liefertermin' => now()->subDay()->toDateString(), 'status' => 'in_arbeit']);
        DB::table('fertigungsauftrag')->where('auftragsnummer', 'FA-1006')->update(['liefertermin' => now()->subDays(2)->toDateString(), 'status' => 'angelegt']);
        DB::table('fertigungsauftrag')->where('auftragsnummer', 'FA-1003')->update(['liefertermin' => now()->toDateString(), 'status' => 'in_arbeit']);
        DB::table('fertigungsauftrag')->where('auftragsnummer', 'FA-1001')->update(['liefertermin' => now()->subDay()->toDateString(), 'status' => 'fertig']);

        Livewire::test(MeisterStartseite::class)
            ->assertSee('FA-1002')->assertSee('FA-1006')
            ->assertDontSee('FA-1003')
            ->assertSeeInOrder(['FA-1006', 'FA-1002']);

        // Ein abgeschlossener Auftrag kann zwar ein freigabebereites Zertifikat haben, ist aber nicht überfällig.
        $this->assertSame(0, DB::table('v_auftragsuebersicht')->where('auftragsnummer', 'FA-1001')->value('verzug'));
    }

    public function test_nur_freigabebereite_und_noch_nicht_freigegebene_zertifikate_erscheinen(): void
    {
        $this->alsBenutzer('1002');
        $fertig = Fertigungsauftrag::where('auftragsnummer', 'FA-1001')->firstOrFail();
        $zertifikat = AuftragZertifikat::where('fertigungsauftrag_id', $fertig->id)->firstOrFail();
        DB::table('fertigungsauftrag')->update(['liefertermin' => now()->addDays(2)->toDateString()]);

        Livewire::test(MeisterStartseite::class)->assertSee('FA-1001')->assertDontSee('FA-1002');
        Livewire::test(Werkszertifikate::class)->call('freigeben', $zertifikat->id)->assertHasNoErrors();
        Livewire::test(MeisterStartseite::class)->assertDontSee('FA-1001')->assertSee('Keine Zertifikate zur Freigabe bereit.');
    }

    public function test_leere_aufgabenliste(): void
    {
        $this->alsBenutzer('1002');
        DB::table('fertigungsauftrag')->update(['liefertermin' => now()->addDays(2)->toDateString()]);
        $fertig = Fertigungsauftrag::where('auftragsnummer', 'FA-1001')->firstOrFail();
        $zertifikat = AuftragZertifikat::where('fertigungsauftrag_id', $fertig->id)->firstOrFail();
        Livewire::test(Werkszertifikate::class)->call('freigeben', $zertifikat->id)->assertHasNoErrors();

        Livewire::test(MeisterStartseite::class)
            ->assertSee('Keine überfälligen offenen Aufträge.')
            ->assertSee('Keine Zertifikate zur Freigabe bereit.');
    }
}
