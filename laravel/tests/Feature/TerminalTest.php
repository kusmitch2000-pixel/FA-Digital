<?php

namespace Tests\Feature;

use App\Actions\Fertigung\AuftragAnlegen;
use App\Livewire\Terminal;
use App\Models\Arbeitsplatz;
use App\Models\Artikel;
use App\Models\Benutzer;
use App\Models\Fertigungsauftrag;
use App\Models\Unterbrechungsgrund;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** Anwendungsfälle 1 bis 5 am Werker-Terminal */
class TerminalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * In den Testdaten sind Säge, Dreh- und Fräsmaschine belegt. Der Testauftrag läuft deshalb
     * über Messplatz (AG 10, mit Checkliste) und Montage (AG 20).
     */
    private function auftragAnFreienPlaetzen(int $menge = 10): Fertigungsauftrag
    {
        $artikel = Artikel::create(['artikelnummer' => 'T-100', 'bezeichnung' => 'Testteil']);
        $messen = $artikel->positionen()->create([
            'ag_nummer' => 10, 'bezeichnung' => 'Messen', 'ruestzeit_min' => 10, 'stueckzeit_min' => 2,
            'arbeitsplatz_id' => Arbeitsplatz::where('nummer', 'AP-40')->value('id'),
        ]);
        $artikel->positionen()->create([
            'ag_nummer' => 20, 'bezeichnung' => 'Montieren', 'ruestzeit_min' => 0, 'stueckzeit_min' => 1,
            'arbeitsplatz_id' => Arbeitsplatz::where('nummer', 'AP-50')->value('id'),
        ]);
        $messen->pruefmerkmale()->create(['reihenfolge' => 1, 'bezeichnung' => 'Länge 30 ±0,05', 'art' => 'messwert',
            'sollwert' => 30, 'untere_grenze' => 29.95, 'obere_grenze' => 30.05, 'einheit' => 'mm']);
        $messen->pruefmerkmale()->create(['reihenfolge' => 2, 'bezeichnung' => 'Kanten entgratet', 'art' => 'sichtpruefung']);

        return app(AuftragAnlegen::class)($this->benutzer('1001'), [
            'artikel_id' => $artikel->id, 'menge' => $menge, 'liefertermin' => now()->addWeek()->toDateString(),
            'kunde' => null, 'bemerkung' => null,
        ], [], true);
    }

    /** Alle Werker aus den Testdaten arbeiten gerade, deshalb ein freier Werker für den Test */
    private function freierWerker(string $personalnummer = '1099'): Benutzer
    {
        return Benutzer::create([
            'personalnummer' => $personalnummer, 'vorname' => 'Tina', 'nachname' => 'Test'.$personalnummer,
            'email' => "werker{$personalnummer}@fa-digital.de", 'passwort_hash' => 'nur-im-test', 'rolle' => 'werker', 'aktiv' => true,
        ]);
    }

    public function test_ganzer_ablauf_vom_start_bis_zur_fertigmeldung(): void
    {
        $auftrag = $this->auftragAnFreienPlaetzen();
        [$messen, $montieren] = $auftrag->arbeitsgaenge;
        $werker = $this->freierWerker();
        $this->actingAs($werker);
        $this->travelTo(now()->startOfMinute());

        $terminal = Livewire::test(Terminal::class)->set('arbeitsplatz', 'AP-40');
        $terminal->assertSee('T-100 Testteil')->call('starten', $messen->id, 'ruesten')->assertHasNoErrors();

        $this->assertSame('laeuft', $messen->fresh()->status);
        $this->assertSame('in_arbeit', $auftrag->fresh()->status);
        $terminal->assertSee($auftrag->auftragsnummer.' · AG 10 Messen');

        // 12 min Rüsten, 20 min Bearbeiten, 15 min Unterbrechung, 8 min Bearbeiten
        $this->travel(12)->minutes();
        $terminal->call('wechseln', 'bearbeiten')->assertHasNoErrors();
        $this->travel(20)->minutes();
        $terminal->set('grund', Unterbrechungsgrund::where('bezeichnung', 'Material fehlt')->value('id'))->call('unterbrechen')->assertHasNoErrors();
        $this->assertSame('unterbrochen', $messen->fresh()->status);
        $this->assertSame('unterbrochen', DB::table('v_hallenansicht')->where('arbeitsplatz_nummer', 'AP-40')->value('zustand'));

        $this->travel(15)->minutes();
        $terminal->call('fortsetzen')->assertHasNoErrors();
        $this->travel(8)->minutes();

        // Fertig melden geht erst, wenn die Checkliste abgearbeitet ist
        $terminal->call('fertigMelden')->assertHasErrors('aktion');

        [$laenge, $kanten] = $messen->pruefpunkte;
        $terminal->set("istwert.{$laenge->id}", '30,08')->call('pruefen', $laenge->id)->assertHasNoErrors();
        $this->assertSame('nicht_in_ordnung', $laenge->fresh()->ergebnis);

        // Nach Nacharbeit neu messen
        $terminal->set("istwert.{$laenge->id}", '30,04')->call('pruefen', $laenge->id);
        $this->assertSame('in_ordnung', $laenge->fresh()->ergebnis);
        $this->assertEquals(30.04, $laenge->fresh()->istwert);
        $terminal->call('pruefen', $kanten->id, 'in_ordnung')->assertHasNoErrors();

        $terminal->set('gutmenge', 9)->set('ausschuss', 1)->call('fertigMelden')->assertHasNoErrors();

        $messen->refresh();
        $this->assertSame('erledigt', $messen->status);
        $this->assertSame([9, 1, $werker->id], [$messen->gutmenge, $messen->ausschuss, $messen->fertig_gemeldet_von]);

        // Die Unterbrechung zählt nicht zur Ist-Zeit: 12 + 20 + 8 = 40 min
        $zeit = DB::table('v_arbeitsgang_zeit')->where('arbeitsgang_id', $messen->id)->first();
        $this->assertEquals([12, 28, 40], [$zeit->ist_ruesten_min, $zeit->ist_bearbeiten_min, $zeit->ist_zeit_min]);
        $this->assertEquals(15, DB::table('v_unterbrechung')->where('auftragsnummer', $auftrag->auftragsnummer)->value('dauer_min'));

        // AG 20 ist jetzt an der Reihe. Nach der letzten Fertigmeldung ist der Auftrag fertig.
        $this->assertSame('bereit', DB::table('v_arbeitsvorrat')->where('arbeitsgang_id', $montieren->id)->value('bereitschaft'));
        $terminal->call('starten', $montieren->id, 'bearbeiten')->assertHasNoErrors()->assertSet('arbeitsplatz', 'AP-50');
        $this->travel(10)->minutes();
        $terminal->set('gutmenge', 9)->set('ausschuss', 0)->call('fertigMelden')->assertHasNoErrors();

        $auftrag->refresh();
        $this->assertSame('fertig', $auftrag->status);
        $this->assertSame($werker->id, $auftrag->abgeschlossen_von);
        $this->assertSame('fertig', DB::table('v_nachkalkulation_auftrag')->where('auftragsnummer', $auftrag->auftragsnummer)->value('status'));
    }

    public function test_reihenfolge_und_belegung_werden_eingehalten(): void
    {
        $auftrag = $this->auftragAnFreienPlaetzen();
        [$messen, $montieren] = $auftrag->arbeitsgaenge;

        $this->actingAs($this->freierWerker('1098'));
        Livewire::test(Terminal::class)->call('starten', $montieren->id, 'ruesten')
            ->assertHasErrors(['aktion' => 'Der vorherige Arbeitsgang ist noch nicht fertig gemeldet.']);

        Livewire::test(Terminal::class)->call('starten', $messen->id, 'ruesten')->assertHasNoErrors();

        // Ein Werker hat immer nur einen Arbeitsgang
        $zweiter = $this->zweiterAuftrag();
        Livewire::test(Terminal::class)->call('starten', $zweiter->arbeitsgaenge[0]->id, 'ruesten')->assertHasErrors('aktion');

        // Ein Arbeitsplatz hat immer nur einen laufenden Arbeitsgang
        $this->actingAs($this->freierWerker('1097'));
        Livewire::test(Terminal::class)->call('starten', $zweiter->arbeitsgaenge[0]->id, 'ruesten')
            ->assertHasErrors(['aktion' => 'An diesem Arbeitsplatz läuft schon ein anderer Arbeitsgang.']);
    }

    public function test_menge_und_checkliste_bei_der_fertigmeldung(): void
    {
        $auftrag = $this->auftragAnFreienPlaetzen(5);
        $messen = $auftrag->arbeitsgaenge[0];
        $this->actingAs($this->freierWerker());

        $terminal = Livewire::test(Terminal::class)->call('starten', $messen->id, 'bearbeiten');

        // Sichtprüfung braucht i. O. oder n. i. O., Messwert eine Zahl
        [$laenge, $kanten] = $messen->pruefpunkte;
        $terminal->call('pruefen', $kanten->id)->assertHasErrors("istwert.{$kanten->id}");
        $terminal->set("istwert.{$laenge->id}", 'abc')->call('pruefen', $laenge->id)->assertHasErrors("istwert.{$laenge->id}");
        $terminal->set("istwert.{$laenge->id}", '30')->call('pruefen', $laenge->id);
        $terminal->call('pruefen', $kanten->id, 'nicht_in_ordnung');

        $terminal->set('gutmenge', 5)->set('ausschuss', 1)->call('fertigMelden')->assertHasErrors('gutmenge');
        $this->assertSame('laeuft', $messen->fresh()->status);

        // n. i. O. blockiert die Fertigmeldung nicht, aber später die Freigabe des Werkszertifikats
        $terminal->set('gutmenge', 4)->set('ausschuss', 1)->call('fertigMelden')->assertHasNoErrors();
        $this->assertSame('erledigt', $messen->fresh()->status);
    }

    private function zweiterAuftrag(): Fertigungsauftrag
    {
        $artikel = Artikel::create(['artikelnummer' => 'T-200', 'bezeichnung' => 'Zweites Testteil']);
        $artikel->positionen()->create([
            'ag_nummer' => 10, 'bezeichnung' => 'Messen', 'ruestzeit_min' => 5, 'stueckzeit_min' => 1,
            'arbeitsplatz_id' => Arbeitsplatz::where('nummer', 'AP-40')->value('id'),
        ]);

        return app(AuftragAnlegen::class)($this->benutzer('1001'), [
            'artikel_id' => $artikel->id, 'menge' => 3, 'liefertermin' => now()->addWeek()->toDateString(), 'kunde' => null, 'bemerkung' => null,
        ], [], true);
    }
}
