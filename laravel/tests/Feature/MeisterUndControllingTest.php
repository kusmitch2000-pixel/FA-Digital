<?php

namespace Tests\Feature;

use App\Livewire\Arbeitsplaene;
use App\Livewire\AuftragDetail;
use App\Livewire\Nachkalkulation;
use App\Livewire\Werkszertifikate;
use App\Models\Arbeitsgang;
use App\Models\ArbeitsplanAenderung;
use App\Models\AuftragZertifikat;
use App\Models\Fertigungsauftrag;
use App\Models\Zeitbuchung;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** Anwendungsfälle 6, 9, 10 und 11 sowie die Übernahme der Soll-Zeiten */
class MeisterUndControllingTest extends TestCase
{
    use RefreshDatabase;

    private function auftrag(string $nummer): Fertigungsauftrag
    {
        return Fertigungsauftrag::where('auftragsnummer', $nummer)->firstOrFail();
    }

    /** Wie Ablauf 5 in datenbank/05_beispielablaeufe.sql */
    public function test_meister_korrigiert_eine_buchung_ohne_sie_zu_loeschen(): void
    {
        $lena = $this->alsBenutzer('1002');
        $auftrag = $this->auftrag('FA-1002');
        $drehen = $auftrag->arbeitsgaenge()->where('ag_nummer', 20)->first();
        $alt = $drehen->zeitbuchungen()->where('taetigkeit', 'ruesten')->where('gueltig', true)->firstOrFail();

        $detail = Livewire::test(AuftragDetail::class, ['auftrag' => $auftrag])
            ->call('korrekturBeginnen', $alt->id)
            ->assertSet('taetigkeit', 'ruesten')
            ->set('ende', $alt->beginn->addMinutes(25)->format('Y-m-d\TH:i'));

        // Ohne Begründung keine Korrektur
        $detail->call('buchungSpeichern')->assertHasErrors('begruendung');
        $this->assertTrue($alt->fresh()->gueltig);

        $detail->set('begruendung', 'Rüsten endete laut Werker nach 25 min')->call('buchungSpeichern')->assertHasNoErrors();

        $this->assertFalse($alt->fresh()->gueltig);
        $neu = Zeitbuchung::where('ersetzt_buchung_id', $alt->id)->firstOrFail();
        $this->assertSame(['korrektur', $lena->id, $alt->benutzer_id], [$neu->quelle, $neu->erfasst_von, $neu->benutzer_id]);
        $this->assertEquals(25, DB::table('v_arbeitsgang_zeit')->where('arbeitsgang_id', $drehen->id)->value('ist_ruesten_min'));

        // Die alte Buchung lässt sich nicht noch einmal korrigieren
        $detail->call('korrekturBeginnen', $alt->id)->set('begruendung', 'zweiter Versuch')->call('buchungSpeichern')->assertHasErrors('aktion');
    }

    public function test_meister_traegt_eine_vergessene_buchung_nach(): void
    {
        $this->alsBenutzer('1002');
        $auftrag = $this->auftrag('FA-1003');
        $jonas = $this->benutzer('1042');

        Livewire::test(AuftragDetail::class, ['auftrag' => $auftrag])
            ->set('arbeitsgangId', $auftrag->arbeitsgaenge()->where('ag_nummer', 10)->value('id'))
            ->set('werkerId', $jonas->id)
            ->set('taetigkeit', 'bearbeiten')
            ->set('beginn', now()->subHours(3)->format('Y-m-d\TH:i'))
            ->set('ende', now()->subHours(2)->format('Y-m-d\TH:i'))
            ->set('begruendung', 'Terminal war ausgefallen')
            ->call('buchungSpeichern')
            ->assertHasNoErrors();

        $nachtrag = Zeitbuchung::where('quelle', 'nachtrag')->where('begruendung', 'Terminal war ausgefallen')->firstOrFail();
        $this->assertSame($jonas->id, $nachtrag->benutzer_id);
        $this->assertEquals(60, $nachtrag->beginn->diffInMinutes($nachtrag->ende));
    }

    /** Letzte Absicherung im Datenmodell: CHECK ck_zeitbuchung_begruendung */
    public function test_datenbank_lehnt_korrektur_ohne_begruendung_ab(): void
    {
        $this->expectException(QueryException::class);

        DB::table('zeitbuchung')->insert([
            'arbeitsgang_id' => Arbeitsgang::value('id'), 'benutzer_id' => $this->benutzer('1042')->id, 'taetigkeit' => 'ruesten',
            'beginn' => now()->subHour(), 'ende' => now(), 'quelle' => 'korrektur', 'begruendung' => null,
            'erfasst_von' => $this->benutzer('1002')->id,
        ]);
    }

    public function test_werkszertifikat_erst_freigeben_wenn_auftrag_fertig_und_alles_in_ordnung(): void
    {
        $this->alsBenutzer('1002');
        $fa1001 = AuftragZertifikat::where('fertigungsauftrag_id', $this->auftrag('FA-1001')->id)->firstOrFail();
        $fa1002 = AuftragZertifikat::where('fertigungsauftrag_id', $this->auftrag('FA-1002')->id)->firstOrFail();

        Livewire::test(Werkszertifikate::class)->call('freigeben', $fa1002->id)->assertHasErrors('aktion');
        $this->assertSame('gefordert', $fa1002->fresh()->status);

        Livewire::test(Werkszertifikate::class)->call('freigeben', $fa1001->id)->assertHasNoErrors();
        $fa1001->refresh();
        $this->assertSame('freigegeben', $fa1001->status);
        $this->assertSame('WZ_FA-1001_2.2.pdf', $fa1001->dateiname);

        $this->get(route('zertifikat.druck', $fa1001))->assertOk()->assertSee('Werkszeugnis 2.2')->assertSee('Freigegeben am');

        // Die Arbeitsvorbereitung sieht Zertifikate, gibt sie aber nicht frei
        $this->alsBenutzer('1001');
        Livewire::test(Werkszertifikate::class)->call('freigeben', $fa1002->id)->assertForbidden();
    }

    public function test_nachkalkulation_abschliessen_nur_durch_controlling(): void
    {
        $this->alsBenutzer('1002');
        Livewire::withQueryParams(['auftrag' => 'FA-1001'])->test(Nachkalkulation::class)
            ->assertSee('468,67 €')
            ->call('abschliessen')
            ->assertForbidden();

        $this->alsBenutzer('1010');
        Livewire::withQueryParams(['auftrag' => 'FA-1001'])->test(Nachkalkulation::class)->call('abschliessen')->assertHasNoErrors();

        $this->assertSame('nachkalkuliert', $this->auftrag('FA-1001')->status);
        $this->assertNotNull($this->auftrag('FA-1001')->nachkalkuliert_am);
    }

    /** Wie Ablauf 7 in datenbank/05_beispielablaeufe.sql */
    public function test_abweichung_in_die_neue_kalkulation_uebernehmen(): void
    {
        $anna = $this->alsBenutzer('1001');
        $position = DB::table('v_sollzeit_vorschlag')->where('artikelnummer', 'W-200')->where('ag_nummer', 20)->value('arbeitsplan_position_id');

        Livewire::withQueryParams(['artikel' => 'W-200'])->test(Arbeitsplaene::class)
            ->assertSee('Rüstzeit 39,0 min übernehmen')
            ->call('uebernehmen', $position, 'ruestzeit')
            ->assertHasNoErrors()
            ->assertDontSee('Rüstzeit 39,0 min übernehmen');

        $aenderung = ArbeitsplanAenderung::where('arbeitsplan_position_id', $position)->latest('id')->firstOrFail();
        $this->assertEquals([30, 39], [$aenderung->alter_wert, $aenderung->neuer_wert]);
        $this->assertSame($anna->id, $aenderung->geaendert_von);

        // Laufende Aufträge behalten ihre Soll-Zeit, der Arbeitsplan hat den neuen Wert
        $this->assertEquals(39, DB::table('arbeitsplan_position')->where('id', $position)->value('ruestzeit_min'));
        $this->assertEquals(30, $this->auftrag('FA-1004')->arbeitsgaenge()->where('ag_nummer', 20)->value('soll_ruestzeit_min'));
    }

    public function test_csv_export_fuer_excel(): void
    {
        $this->alsBenutzer('1010');

        $antwort = $this->get(route('auswertung.export', 'auftraege'))->assertOk();
        $this->assertStringStartsWith('text/csv', $antwort->headers->get('Content-Type'));
        $csv = $antwort->getContent();
        $this->assertStringStartsWith("\xEF\xBB\xBFAuftrag;Artikel-Nr.;", $csv);
        $this->assertStringContainsString('FA-1001;W-200;Antriebswelle Ø40;50;', $csv);
        $this->assertStringContainsString(';431,25;468,67;8,7;8,63;9,76;fertig', $csv);

        $this->get(route('auswertung.export', 'unterbrechungen'))->assertOk()->assertSee('Maschinenstörung;nein;3;145;', false);
        $this->get('/auswertung/unbekannt.csv')->assertNotFound();
    }
}
