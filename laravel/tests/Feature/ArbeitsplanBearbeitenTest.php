<?php

namespace Tests\Feature;

use App\Livewire\Arbeitsplaene;
use App\Models\ArbeitsplanAenderung;
use App\Models\ArbeitsplanPosition;
use App\Models\Fertigungsauftrag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Die Arbeitsvorbereitung ändert Soll-Zeiten im Arbeitsplan von Hand, immer mit Begründung */
class ArbeitsplanBearbeitenTest extends TestCase
{
    use RefreshDatabase;

    /** V-410 · AG 20 Kanten: Rüstzeit 20 min, Stückzeit 3 min */
    private function kanten(): ArbeitsplanPosition
    {
        return ArbeitsplanPosition::whereHas('artikel', fn ($a) => $a->where('artikelnummer', 'V-410'))->where('ag_nummer', 20)->firstOrFail();
    }

    public function test_soll_zeit_aendern_landet_mit_begruendung_in_der_historie(): void
    {
        $anna = $this->alsBenutzer('1001');
        $kanten = $this->kanten();

        Livewire::withQueryParams(['artikel' => 'V-410'])->test(Arbeitsplaene::class)
            ->call('bearbeitenBeginnen', $kanten->id)
            ->assertSet('neueRuestzeit', 20.0)
            ->assertSee('Änderung speichern')
            ->set('neueRuestzeit', 24)
            ->set('aenderungBegruendung', 'Neues Oberwerkzeug, Rüsten im Versuch gemessen')
            ->call('aenderungSpeichern')
            ->assertHasNoErrors()
            ->assertSet('bearbeitetId', null)
            ->assertSee('Rüstzeit von 20,00 auf 24,00 min geändert')
            ->assertSee('Neues Oberwerkzeug, Rüsten im Versuch gemessen');

        // Nur die geänderte Rüstzeit steht in der Historie, die Stückzeit blieb gleich
        $aenderung = ArbeitsplanAenderung::where('arbeitsplan_position_id', $kanten->id)->latest('id')->firstOrFail();
        $this->assertSame('ruestzeit', $aenderung->feld);
        $this->assertEquals([20, 24], [$aenderung->alter_wert, $aenderung->neuer_wert]);
        $this->assertSame($anna->id, $aenderung->geaendert_von);
        $this->assertSame(1, ArbeitsplanAenderung::where('arbeitsplan_position_id', $kanten->id)->count());
        $this->assertEquals(24, $kanten->fresh()->ruestzeit_min);

        // Bestehende Aufträge behalten ihre kopierte Soll-Zeit
        $fa1001 = Fertigungsauftrag::where('auftragsnummer', 'FA-1001')->firstOrFail();
        $this->assertEquals(20, $fa1001->arbeitsgaenge()->where('ag_nummer', 20)->value('soll_ruestzeit_min'));
    }

    public function test_beide_zeiten_geben_zwei_eintraege(): void
    {
        $this->alsBenutzer('1001');
        $kanten = $this->kanten();

        Livewire::withQueryParams(['artikel' => 'V-410'])->test(Arbeitsplaene::class)
            ->call('bearbeitenBeginnen', $kanten->id)
            ->set('neueRuestzeit', 29)
            ->set('neueStueckzeit', 3.2)
            ->set('aenderungBegruendung', 'Abgleich mit der Nachkalkulation FA-1001')
            ->call('aenderungSpeichern')
            ->assertHasNoErrors();

        $this->assertEqualsCanonicalizing(['ruestzeit', 'stueckzeit'],
            ArbeitsplanAenderung::where('arbeitsplan_position_id', $kanten->id)->pluck('feld')->all());
        $this->assertEquals(3.2, $kanten->fresh()->stueckzeit_min);
    }

    public function test_ohne_begruendung_oder_ohne_aenderung_wird_nichts_gespeichert(): void
    {
        $this->alsBenutzer('1001');
        $kanten = $this->kanten();

        $seite = Livewire::withQueryParams(['artikel' => 'V-410'])->test(Arbeitsplaene::class)
            ->call('bearbeitenBeginnen', $kanten->id)
            ->set('neueRuestzeit', 25)
            ->call('aenderungSpeichern')
            ->assertHasErrors(['aenderungBegruendung' => 'required']);

        $seite->set('aenderungBegruendung', 'nur zum Test')->set('neueRuestzeit', 20)
            ->call('aenderungSpeichern')
            ->assertHasErrors('aktion');

        $this->assertSame(0, ArbeitsplanAenderung::where('arbeitsplan_position_id', $kanten->id)->count());
        $this->assertEquals(20, $kanten->fresh()->ruestzeit_min);
    }

    public function test_nur_arbeitsvorbereitung_und_admin_duerfen_aendern(): void
    {
        $kanten = $this->kanten();

        $this->alsBenutzer('1010');
        Livewire::withQueryParams(['artikel' => 'V-410'])->test(Arbeitsplaene::class)->assertForbidden();

        $this->alsBenutzer('1002');
        Livewire::withQueryParams(['artikel' => 'V-410'])->test(Arbeitsplaene::class)->assertForbidden();

        $this->assertEquals(20, $kanten->fresh()->ruestzeit_min);
    }
}
