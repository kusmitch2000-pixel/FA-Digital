<?php

namespace Tests\Feature;

use App\Actions\Fertigung\AuftragAnlegen;
use App\Livewire\AuftragDetail;
use App\Livewire\Messung;
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
        foreach ($auftrag->arbeitsgaenge as $ag) {
            DB::table('arbeitsgang_zuordnung')->insert(['arbeitsgang_id' => $ag->id, 'benutzer_id' => $werker->id]);
        }
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

        $werker = $this->freierWerker('1098');
        foreach ($auftrag->arbeitsgaenge as $ag) {
            DB::table('arbeitsgang_zuordnung')->insert(['arbeitsgang_id' => $ag->id, 'benutzer_id' => $werker->id]);
        }
        $this->actingAs($werker);
        Livewire::test(Terminal::class)->call('starten', $montieren->id, 'ruesten')
            ->assertHasErrors(['aktion' => 'Der vorherige Arbeitsgang ist noch nicht fertig gemeldet.']);

        Livewire::test(Terminal::class)->call('starten', $messen->id, 'ruesten')->assertHasNoErrors();

        // Ein Werker hat immer nur einen Arbeitsgang
        $zweiter = $this->zweiterAuftrag();
        DB::table('arbeitsgang_zuordnung')->insert(['arbeitsgang_id' => $zweiter->arbeitsgaenge[0]->id, 'benutzer_id' => $werker->id]);
        Livewire::test(Terminal::class)->call('starten', $zweiter->arbeitsgaenge[0]->id, 'ruesten')->assertHasErrors('aktion');

        // Ein Arbeitsplatz hat immer nur einen laufenden Arbeitsgang
        $zweiterWerker = $this->freierWerker('1097');
        DB::table('arbeitsgang_zuordnung')->insert(['arbeitsgang_id' => $zweiter->arbeitsgaenge[0]->id, 'benutzer_id' => $zweiterWerker->id]);
        $this->actingAs($zweiterWerker);
        Livewire::test(Terminal::class)->call('starten', $zweiter->arbeitsgaenge[0]->id, 'ruesten')
            ->assertHasErrors(['aktion' => 'An diesem Arbeitsplatz läuft schon ein anderer Arbeitsgang.']);
    }

    public function test_menge_und_checkliste_bei_der_fertigmeldung(): void
    {
        $auftrag = $this->auftragAnFreienPlaetzen(5);
        $messen = $auftrag->arbeitsgaenge[0];
        $werker = $this->freierWerker();
        DB::table('arbeitsgang_zuordnung')->insert(['arbeitsgang_id' => $messen->id, 'benutzer_id' => $werker->id]);
        $this->actingAs($werker);

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

    public function test_zuordnung_schuetzt_arbeitsgang_und_notizen_sind_sichtbar(): void
    {
        $auftrag = $this->auftragAnFreienPlaetzen();
        $ag = $auftrag->arbeitsgaenge[0];
        $werker = $this->freierWerker();
        $anderer = $this->freierWerker('1096');
        $this->actingAs($this->benutzer('1000'));
        Livewire::test(AuftragDetail::class, ['auftrag' => $auftrag])
            ->set('zuordnungArbeitsgang', $ag->id)->set('zuordnungWerker', $werker->id)
            ->call('zuordnen')->assertHasNoErrors();
        $this->actingAs($anderer);
        Livewire::test(Terminal::class)->assertDontSee('T-100 Testteil')
            ->call('starten', $ag->id, 'bearbeiten')->assertHasErrors('aktion');
        $this->actingAs($werker);
        $terminal = Livewire::test(Terminal::class)->assertSee('T-100 Testteil')
            ->call('starten', $ag->id, 'bearbeiten')->assertHasNoErrors()
            ->set('notiz', 'Material beschädigt')->call('notizSpeichern')->assertHasNoErrors();
        $terminal->set('grund', Unterbrechungsgrund::where('bezeichnung', 'Material fehlt')->value('id'))
            ->set('unterbrechungBemerkung', 'Warte auf Lieferung')->call('unterbrechen')->assertHasNoErrors();
        $this->assertDatabaseHas('arbeitsgang_notiz', ['arbeitsgang_id' => $ag->id, 'benutzer_id' => $werker->id, 'text' => 'Material beschädigt']);
        $this->assertDatabaseHas('unterbrechung', ['arbeitsgang_id' => $ag->id, 'bemerkung' => 'Warte auf Lieferung']);
        $this->get('/auftraege')->assertForbidden();
        $this->actingAs($this->benutzer('1000'));
        Livewire::test(AuftragDetail::class, ['auftrag' => $auftrag])->assertSee('Material beschädigt')
            ->call('zuordnungEntfernen', $ag->id, $werker->id)->assertHasErrors('aktion');
    }

    public function test_zertifikatsauftrag_bleibt_bis_zur_vollstaendigen_messung_offen(): void
    {
        $auftrag = $this->auftragAnFreienPlaetzen();
        $auftrag->zertifikate()->create(['zertifikatsart_id' => DB::table('zertifikatsart')->where('kuerzel', '2.2')->value('id')]);
        [$messen, $montieren] = $auftrag->arbeitsgaenge;
        $werker = $this->freierWerker();
        foreach ($auftrag->arbeitsgaenge as $ag) {
            DB::table('arbeitsgang_zuordnung')->insert(['arbeitsgang_id' => $ag->id, 'benutzer_id' => $werker->id]);
        }
        $this->actingAs($werker);
        $terminal = Livewire::test(Terminal::class)->call('starten', $messen->id, 'bearbeiten')
            ->set('gutmenge', 10)->set('ausschuss', 0)->call('fertigMelden')->assertHasNoErrors()
            ->assertRedirect(route('terminal.messung', $messen->id));
        $this->assertSame('messen', $messen->fresh()->status);
        $this->assertNull($auftrag->fresh()->abgeschlossen_am);
        $this->assertSame('wartet', DB::table('v_arbeitsvorrat')->where('arbeitsgang_id', $montieren->id)->value('bereitschaft'));
        Livewire::test(Terminal::class)->assertSee('Offene Messungen')->assertSee('Messen');
        [$laenge, $kanten] = $messen->pruefpunkte;
        $messung = Livewire::test(Messung::class, ['arbeitsgang' => $messen->id]);
        $messung->call('abschliessen')->assertHasErrors('aktion');
        $messung->set("istwert.{$laenge->id}", 'ungültig')->call('speichern', $laenge->id)->assertHasErrors("istwert.{$laenge->id}");
        $messung->set("istwert.{$laenge->id}", '30,00')->call('speichern', $laenge->id)->assertHasNoErrors();
        $messung->call('abschliessen')->assertHasErrors('aktion');
        $anderer = $this->freierWerker('1096');
        $this->actingAs($anderer);
        $this->get(route('terminal.messung', $messen->id))->assertForbidden();
        $this->actingAs($werker);
        Livewire::test(Messung::class, ['arbeitsgang' => $messen->id])
            ->assertSee('30,000')->call('speichern', $kanten->id, 'in_ordnung')->assertHasNoErrors()
            ->call('abschliessen')->assertHasNoErrors()->assertRedirect(route('terminal'));
        $this->assertSame('erledigt', $messen->fresh()->status);
        $this->assertSame('bereit', DB::table('v_arbeitsvorrat')->where('arbeitsgang_id', $montieren->id)->value('bereitschaft'));
        Livewire::test(Terminal::class)->call('starten', $montieren->id, 'bearbeiten')
            ->set('gutmenge', 10)->call('fertigMelden')->assertHasNoErrors();
        // Ein weiterer Arbeitsgang ohne Prüfpunkte ist fertig, wenn die übrigen Messungen vorliegen.
        $this->assertSame('fertig', $auftrag->fresh()->status);
        $this->assertSame(1, (int) DB::table('v_zertifikat_status')->where('auftragsnummer', $auftrag->auftragsnummer)->value('bereit_zur_freigabe'));
        $this->assertSame($werker->id, $laenge->fresh()->geprueft_von);
        $this->assertNotNull($laenge->fresh()->geprueft_am);
    }

    public function test_zertifikatsauftrag_ohne_pruefpunkte_bleibt_offen(): void
    {
        $auftrag = $this->zweiterAuftrag();
        $auftrag->zertifikate()->create(['zertifikatsart_id' => DB::table('zertifikatsart')->where('kuerzel', '2.2')->value('id')]);
        $ag = $auftrag->arbeitsgaenge[0];
        $werker = $this->freierWerker();
        DB::table('arbeitsgang_zuordnung')->insert(['arbeitsgang_id' => $ag->id, 'benutzer_id' => $werker->id]);
        $this->actingAs($werker);
        Livewire::test(Terminal::class)->call('starten', $ag->id, 'bearbeiten')->set('gutmenge', 3)->call('fertigMelden')->assertHasNoErrors();
        $this->assertSame('messen', $auftrag->fresh()->status);
        Livewire::test(Messung::class, ['arbeitsgang' => $ag->id])->call('abschliessen')->assertHasErrors('aktion');
        $this->assertSame('messen', $auftrag->fresh()->status);
    }

    public function test_zertifikatsauftrag_wird_nach_letzter_messung_fertig_aber_nicht_automatisch_freigegeben(): void
    {
        $auftrag = $this->zweiterAuftrag();
        $auftrag->zertifikate()->create(['zertifikatsart_id' => DB::table('zertifikatsart')->where('kuerzel', '2.2')->value('id')]);
        $ag = $auftrag->arbeitsgaenge[0];
        $punkt = $ag->pruefpunkte()->create(['reihenfolge' => 1, 'bezeichnung' => 'Oberfläche', 'art' => 'sichtpruefung', 'ergebnis' => 'offen']);
        $werker = $this->freierWerker();
        DB::table('arbeitsgang_zuordnung')->insert(['arbeitsgang_id' => $ag->id, 'benutzer_id' => $werker->id]);
        $this->actingAs($werker);
        Livewire::test(Terminal::class)->call('starten', $ag->id, 'bearbeiten')->set('gutmenge', 3)->call('fertigMelden')->assertHasNoErrors();
        $this->assertSame('messen', $auftrag->fresh()->status);
        $this->assertSame(0, (int) DB::table('v_zertifikat_status')->where('auftragsnummer', $auftrag->auftragsnummer)->value('bereit_zur_freigabe'));
        Livewire::test(Messung::class, ['arbeitsgang' => $ag->id])
            ->call('speichern', $punkt->id, 'nicht_in_ordnung')->call('abschliessen')->assertHasNoErrors();
        $this->assertSame('fertig', $auftrag->fresh()->status);
        $this->assertSame('gefordert', $auftrag->zertifikate()->first()->status);
        $this->assertSame(0, (int) DB::table('v_zertifikat_status')->where('auftragsnummer', $auftrag->auftragsnummer)->value('bereit_zur_freigabe'));
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
