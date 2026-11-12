<?php

namespace Database\Seeders;

use App\Actions\Fertigung\AuftragAnlegen;
use App\Models\Arbeitsplatz;
use App\Models\Artikel;
use App\Models\Benutzer;
use App\Models\Zertifikatsart;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ZertifikatWerkerDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('Dieses Beispiel ist nur in APP_ENV=local erlaubt.');
        }

        DB::transaction(function () {
            if (Benutzer::where('email', 'demo.messung@fa-digital.local')->exists()) {
                $this->command->info('Demo-Werker existiert bereits; keine Daten geändert.');

                return;
            }
            if (Benutzer::where('personalnummer', 'DEMO-M')->exists()
                || Arbeitsplatz::where('nummer', 'DEMO-MESS')->exists()
                || Artikel::where('artikelnummer', 'DEMO-MESSUNG')->exists()) {
                throw new RuntimeException('Demo-Kennung bereits vergeben; bitte vorhandene Daten prüfen.');
            }
            $admin = Benutzer::where('rolle', 'admin')->where('aktiv', true)->firstOrFail();
            $zertifikat = Zertifikatsart::where('kuerzel', '2.2')->firstOrFail();
            $werker = Benutzer::create([
                'personalnummer' => 'DEMO-M', 'vorname' => 'Mia', 'nachname' => 'Messung',
                'email' => 'demo.messung@fa-digital.local', 'passwort_hash' => 'MessungDemo123!',
                'rolle' => 'werker', 'aktiv' => true,
            ]);
            $platz = Arbeitsplatz::create(['nummer' => 'DEMO-MESS', 'bezeichnung' => 'Demo-Messarbeitsplatz', 'kostenstelle' => 'DEMO', 'stundensatz' => 60, 'aktiv' => true]);
            $artikel = Artikel::create(['artikelnummer' => 'DEMO-MESSUNG', 'bezeichnung' => 'Demo-Bauteil mit Werkszeugnis', 'aktiv' => true]);
            $position = $artikel->positionen()->create(['ag_nummer' => 10, 'bezeichnung' => 'Bauteil fertigen', 'arbeitsplatz_id' => $platz->id, 'ruestzeit_min' => 10, 'stueckzeit_min' => 5]);
            $position->pruefmerkmale()->create(['reihenfolge' => 1, 'bezeichnung' => 'Länge', 'art' => 'messwert', 'sollwert' => 100, 'untere_grenze' => 99.9, 'obere_grenze' => 100.1, 'einheit' => 'mm', 'zertifikatsart_id' => $zertifikat->id]);
            $position->pruefmerkmale()->create(['reihenfolge' => 2, 'bezeichnung' => 'Oberfläche ohne sichtbare Beschädigung', 'art' => 'sichtpruefung', 'zertifikatsart_id' => $zertifikat->id]);
            $auftrag = app(AuftragAnlegen::class)($admin, [
                'artikel_id' => $artikel->id, 'menge' => 5, 'liefertermin' => now()->addWeek()->toDateString(),
                'kunde' => null, 'bemerkung' => 'Beispielauftrag mit Werkszeugnis 2.2',
            ], [$zertifikat->id], true);
            DB::table('arbeitsgang_zuordnung')->insert(['arbeitsgang_id' => $auftrag->arbeitsgaenge()->firstOrFail()->id, 'benutzer_id' => $werker->id]);
            $this->command->info("Demo-Werker und Zertifikatsauftrag {$auftrag->auftragsnummer} angelegt.");
        });
    }
}
