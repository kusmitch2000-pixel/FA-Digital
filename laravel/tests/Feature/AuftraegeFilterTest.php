<?php

namespace Tests\Feature;

use App\Livewire\Auftraege;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Suche und Filter der Auftragsübersicht (Anwendungsfall 8) */
class AuftraegeFilterTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<string> sichtbare Auftragsnummern */
    private function nummern(mixed $seite): array
    {
        return $seite->viewData('auftraege')->pluck('auftragsnummer')->sort()->values()->all();
    }

    public function test_suche_findet_auftrag_artikel_und_kunde(): void
    {
        $this->alsBenutzer('1001');
        $seite = Livewire::test(Auftraege::class);

        $this->assertSame(['FA-0997', 'FA-1002', 'FA-1004'], $this->nummern($seite->set('suche', 'hansa')));
        $this->assertSame(['FA-1000', 'FA-1005'], $this->nummern($seite->set('suche', 'Wanne')));
        $this->assertSame(['FA-1006'], $this->nummern($seite->set('suche', 'W-200')));
        $seite->set('suche', 'gibt es nicht')->assertSee('Kein Auftrag passt zum Filter.')->assertSee('0 von 8 Aufträgen');
    }

    public function test_status_offen_und_verzug(): void
    {
        $this->alsBenutzer('1001');
        $seite = Livewire::test(Auftraege::class)->assertSee('Alle 8 Aufträge')->assertSee('in Arbeit (4)');

        $this->assertSame(['FA-1000', 'FA-1002', 'FA-1003', 'FA-1004', 'FA-1005', 'FA-1006'], $this->nummern($seite->set('status', 'offen')));
        $this->assertSame(['FA-1006'], $this->nummern($seite->set('status', 'angelegt')));

        $seite->set('status', '')->set('verzug', true);
        $this->assertSame(['FA-1000'], $this->nummern($seite));

        // Filter kombiniert und wieder zurückgesetzt
        $this->assertSame([], $this->nummern($seite->set('status', 'angelegt')));
        $seite->call('zuruecksetzen')->assertSet('status', '')->assertSet('verzug', false)->assertSee('Alle 8 Aufträge');
    }

    public function test_filter_steht_in_der_adresse(): void
    {
        $this->alsBenutzer('1001');

        $seite = Livewire::withQueryParams(['status' => 'in_arbeit', 'suche' => 'V-4'])->test(Auftraege::class);
        $this->assertSame(['FA-1000', 'FA-1003'], $this->nummern($seite));
    }
}
