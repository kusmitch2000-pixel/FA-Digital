<?php

namespace Tests\Feature;

use App\Livewire\Arbeitsplaene;
use App\Livewire\AuftragAnlegen;
use App\Livewire\AuftragDetail;
use App\Livewire\Zertifikatsvorlagen;
use App\Models\ArbeitsplanPosition;
use App\Models\Artikel;
use App\Models\Fertigungsauftrag;
use App\Models\Pruefmerkmal;
use App\Models\Pruefpunkt;
use App\Models\Zertifikatsart;
use App\Support\Zertifikate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Prüfmerkmale pflegen, Zertifikatsvorlagen je Artikel und Aufträge vor dem Start bearbeiten */
class ArbeitsvorbereitungTest extends TestCase
{
    use RefreshDatabase;

    private function position(string $artikel, int $ag): ArbeitsplanPosition
    {
        return ArbeitsplanPosition::whereHas('artikel', fn ($a) => $a->where('artikelnummer', $artikel))->where('ag_nummer', $ag)->firstOrFail();
    }

    private function zertifikat(string $kuerzel): Zertifikatsart
    {
        return Zertifikatsart::where('kuerzel', $kuerzel)->firstOrFail();
    }

    private function auftrag(string $nummer): Fertigungsauftrag
    {
        return Fertigungsauftrag::where('auftragsnummer', $nummer)->firstOrFail();
    }

    public function test_pruefmerkmal_anlegen_aendern_loeschen(): void
    {
        $this->alsBenutzer('1001');
        $kanten = $this->position('V-410', 20);
        $seite = Livewire::withQueryParams(['artikel' => 'V-410'])->test(Arbeitsplaene::class)
            ->call('merkmaleOeffnen', $kanten->id)
            ->assertSee('Prüfmerkmale von AG 20 Kanten');

        // Messwert ohne Grenzen geht nicht
        $seite->set('merkmalBezeichnung', 'Schenkellänge 40 ±0,5')->call('merkmalSpeichern')
            ->assertHasErrors(['merkmalUnten' => 'required', 'merkmalOben' => 'required']);

        $seite->set('merkmalSoll', 40)->set('merkmalUnten', 39.5)->set('merkmalOben', 40.5)
            ->call('merkmalSpeichern')->assertHasNoErrors()->assertSee('Schenkellänge 40 ±0,5');
        $neu = Pruefmerkmal::where('bezeichnung', 'Schenkellänge 40 ±0,5')->firstOrFail();
        $this->assertSame([$kanten->id, 2, 'mm', null], [$neu->arbeitsplan_position_id, $neu->reihenfolge, $neu->einheit, $neu->zertifikatsart_id]);

        // Untere Grenze über der oberen geht nicht
        $seite->call('merkmalBearbeiten', $neu->id)->set('merkmalUnten', 41)->call('merkmalSpeichern')->assertHasErrors(['merkmalOben' => 'gte']);

        // Als Sichtprüfung, nur beim Lackprüfprotokoll
        $seite->set('merkmalArt', 'sichtpruefung')->set('merkmalZertifikat', $this->zertifikat('LP')->id)->call('merkmalSpeichern')->assertHasNoErrors();
        $neu->refresh();
        $this->assertSame(['sichtpruefung', null, $this->zertifikat('LP')->id], [$neu->art, $neu->untere_grenze, $neu->zertifikatsart_id]);

        $seite->call('merkmalLoeschen', $neu->id)->assertHasNoErrors();
        $this->assertFalse(Pruefmerkmal::whereKey($neu->id)->exists());
    }

    public function test_neues_pruefmerkmal_kommt_bei_neuen_auftraegen_in_die_checkliste(): void
    {
        $this->alsBenutzer('1001');
        $drehen = $this->position('W-200', 10);
        Livewire::withQueryParams(['artikel' => 'W-200'])->test(Arbeitsplaene::class)
            ->call('merkmaleOeffnen', $drehen->id)
            ->set('merkmalBezeichnung', 'Fase 1x45° vorhanden')->set('merkmalArt', 'sichtpruefung')
            ->call('merkmalSpeichern')->assertHasNoErrors();

        Livewire::test(AuftragAnlegen::class)->set('artikelId', $drehen->artikel_id)->set('menge', 5)
            ->set('liefertermin', now()->addWeek()->toDateString())->call('speichern')->assertHasNoErrors();

        $auftrag = $this->auftrag('FA-1007');
        $this->assertTrue(Pruefpunkt::whereIn('arbeitsgang_id', $auftrag->arbeitsgaenge()->pluck('id'))->where('bezeichnung', 'Fase 1x45° vorhanden')->exists());
        // FA-1006 über W-200 ist älter und behält seine Checkliste
        $this->assertFalse(Pruefpunkt::whereIn('arbeitsgang_id', $this->auftrag('FA-1006')->arbeitsgaenge()->pluck('id'))->where('bezeichnung', 'Fase 1x45° vorhanden')->exists());
    }

    public function test_zertifikate_gelten_nur_fuer_passende_artikel(): void
    {
        $flansch = Artikel::where('artikelnummer', 'F-310')->value('id');
        $wanne = Artikel::where('artikelnummer', 'V-420')->value('id');

        // DP hat Prüfmerkmale nur bei der Späneschutzwanne, 2.1 und 3.1 haben keine und gelten überall
        $this->assertSame(['2.1', '2.2', '3.1'], Zertifikate::fuerArtikel($flansch)->pluck('kuerzel')->all());
        $this->assertSame(['2.1', '3.1', 'DP', 'LP'], Zertifikate::fuerArtikel($wanne)->pluck('kuerzel')->all());

        $this->alsBenutzer('1001');
        $seite = Livewire::test(AuftragAnlegen::class)->set('artikelId', $wanne)->assertSee('Dichtheitsprotokoll')
            ->set('zertifikate', [(string) $this->zertifikat('DP')->id, (string) $this->zertifikat('2.1')->id]);

        // Wechsel auf den Flansch: DP fällt weg, 2.1 bleibt
        $seite->set('artikelId', $flansch)->assertDontSee('Dichtheitsprotokoll')
            ->assertSet('zertifikate', [(string) $this->zertifikat('2.1')->id]);

        // Auch ohne Oberfläche lehnt der Server ein unpassendes Zertifikat ab
        $seite->set('zertifikate', [(string) $this->zertifikat('DP')->id])->set('liefertermin', now()->addWeek()->toDateString())
            ->call('speichern')->assertHasErrors('zertifikate');
        $this->assertFalse(Fertigungsauftrag::where('auftragsnummer', 'FA-1007')->exists());
    }

    public function test_zertifikatsvorlage_anlegen_und_loeschen(): void
    {
        $this->alsBenutzer('1001');
        $seite = Livewire::test(Zertifikatsvorlagen::class)->assertSee('Lackprüfprotokoll')->assertSee('DIN EN 10204');

        $seite->set('kuerzel', 'mp')->set('bezeichnung', 'Maßprotokoll')->call('speichern')->assertHasNoErrors()->assertSee('Maßprotokoll');
        $this->assertTrue(Zertifikatsart::where('kuerzel', 'MP')->exists());

        $seite->set('kuerzel', 'LP')->set('bezeichnung', 'Doppelt')->call('speichern')->assertHasErrors(['kuerzel' => 'unique']);
        $seite->set('kuerzel', '2.2')->set('bezeichnung', 'Norm')->call('speichern')->assertHasErrors('kuerzel');

        // LP wird in Aufträgen gefordert und hat Prüfmerkmale: bleibt
        $seite->call('loeschen', $this->zertifikat('LP')->id)->assertHasErrors('aktion');
        $this->assertTrue(Zertifikatsart::where('kuerzel', 'LP')->exists());

        $seite->call('loeschen', $this->zertifikat('MP')->id)->assertHasNoErrors();
        $this->assertFalse(Zertifikatsart::where('kuerzel', 'MP')->exists());

        // Die Meisterin sieht die Vorlagen, ändert sie aber nicht
        $this->alsBenutzer('1002');
        Livewire::test(Zertifikatsvorlagen::class)->assertSee('Lackprüfprotokoll')->assertDontSee('+ Neues Zertifikat')
            ->call('loeschen', $this->zertifikat('DP')->id)->assertForbidden();
    }

    public function test_auftrag_vor_dem_start_bearbeiten(): void
    {
        $this->alsBenutzer('1001');
        $fa1006 = $this->auftrag('FA-1006');
        $pruefpunkte = fn () => Pruefpunkt::whereIn('arbeitsgang_id', $fa1006->arbeitsgaenge()->pluck('id'))->count();
        $this->assertSame(5, $pruefpunkte());

        $seite = Livewire::test(AuftragDetail::class, ['auftrag' => $fa1006])
            ->call('bearbeitenBeginnen')->assertSet('menge', 120)->assertSee('Auftrag bearbeiten')
            ->set('menge', 100)->set('liefertermin', now()->addWeeks(5)->toDateString())->set('kunde', 'Ostsee Fräsenbau GmbH')
            ->set('zertifikate', [])
            ->call('aenderungSpeichern')->assertHasNoErrors()->assertSet('bearbeiten', false);

        $fa1006->refresh();
        $this->assertSame([100, 'Ostsee Fräsenbau GmbH'], [$fa1006->menge, $fa1006->kunde]);
        $this->assertSame(0, $fa1006->zertifikate()->count());
        $this->assertSame(3, $pruefpunkte(), 'Die zwei Prüfpunkte des Werkszeugnisses 2.2 fallen weg');

        // Zertifikat wieder dazu: die Prüfpunkte kommen zurück
        $seite->call('bearbeitenBeginnen')->set('zertifikate', [(string) $this->zertifikat('2.2')->id])->call('aenderungSpeichern')->assertHasNoErrors();
        $this->assertSame(5, $pruefpunkte());

        // Liefertermin in der Vergangenheit geht nicht
        $seite->call('bearbeitenBeginnen')->set('liefertermin', now()->subDay()->toDateString())->call('aenderungSpeichern')
            ->assertHasErrors(['liefertermin' => 'after_or_equal']);
    }

    public function test_gestartete_auftraege_und_andere_rollen_aendern_nichts(): void
    {
        $this->alsBenutzer('1001');
        Livewire::test(AuftragDetail::class, ['auftrag' => $this->auftrag('FA-1002')])
            ->assertDontSee('wire:click="bearbeitenBeginnen"', false)
            ->call('bearbeitenBeginnen')->assertHasErrors('aktion');

        $this->alsBenutzer('1002');
        Livewire::test(AuftragDetail::class, ['auftrag' => $this->auftrag('FA-1006')])->call('bearbeitenBeginnen')->assertForbidden();
    }
}
