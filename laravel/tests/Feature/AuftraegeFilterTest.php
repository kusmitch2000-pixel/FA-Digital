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

    public function test_status_knoepfe_und_zuruecksetzen(): void
    {
        $this->alsBenutzer('1001');
        $seite = Livewire::test(Auftraege::class)->assertSee('Alle 8 Aufträge');

        $this->assertSame(['FA-1000', 'FA-1002', 'FA-1003', 'FA-1004', 'FA-1005', 'FA-1006'], $this->nummern($seite->set('status', 'offen')));
        $this->assertSame(['FA-1006'], $this->nummern($seite->set('status', 'angelegt')));

        // Suche und Status kombiniert, danach zurückgesetzt
        $this->assertSame([], $this->nummern($seite->set('suche', 'hansa')));
        $seite->call('zuruecksetzen')->assertSet('status', '')->assertSet('suche', '')->assertSee('Alle 8 Aufträge');
    }

    /** Nach dem Status gefiltert sieht man, an welchem Arbeitsplatz jedes Teil gerade ist */
    public function test_aktueller_prozess_je_auftrag(): void
    {
        $this->alsBenutzer('1001');

        Livewire::test(Auftraege::class)->set('status', 'in_arbeit')
            ->assertSee('Aktueller Prozess')
            ->assertSeeInOrder(['FA-1000', 'Abkantpresse', 'unterbrochen', 'AG 20 Kanten zur Wanne'])
            ->assertSeeHtmlInOrder(['FA-1002', 'CNC-Drehmaschine', 'badge laeuft', 'AG 20 Drehen'])
            ->assertSeeInOrder(['FA-1003', 'Abkantpresse', 'bereit', 'AG 20 Kanten'])
            ->assertSeeHtmlInOrder(['FA-1004', 'Laserschneidanlage', 'badge laeuft', 'AG 10 Ronde auslasern']);

        Livewire::test(Auftraege::class)->set('status', 'angelegt')
            ->assertSeeInOrder(['FA-1006', 'CNC-Drehmaschine', 'noch nicht freigegeben', 'AG 10 Drehen']);
    }

    public function test_filter_steht_in_der_adresse(): void
    {
        $this->alsBenutzer('1001');

        $seite = Livewire::withQueryParams(['status' => 'in_arbeit', 'suche' => 'V-4'])->test(Auftraege::class);
        $this->assertSame(['FA-1000', 'FA-1003'], $this->nummern($seite));
    }
}
