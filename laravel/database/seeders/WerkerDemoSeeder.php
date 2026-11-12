<?php

namespace Database\Seeders;

use App\Actions\Fertigung\AuftragAnlegen;
use App\Models\Arbeitsplatz;
use App\Models\Artikel;
use App\Models\Benutzer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Ein ausdrücklich aufgerufenes Beispiel für eine lokale Entwicklungsdatenbank. */
class WerkerDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('Der Werker-Demodatensatz ist nur in APP_ENV=local erlaubt.');
        }

        DB::transaction(function () {
            if (Benutzer::where('email', 'demo.werker@fa-digital.local')->exists()) {
                $this->command->info('Demo-Werker existiert bereits; keine vorhandenen Daten geändert.');

                return;
            }
            if (Benutzer::where('personalnummer', 'DEMO-W')->exists()
                || Arbeitsplatz::where('nummer', 'DEMO-AP')->exists()
                || Artikel::where('artikelnummer', 'DEMO-WERKER')->exists()) {
                throw new RuntimeException('Demo-Kennung bereits vergeben; bitte vorhandene Daten prüfen.');
            }
            $admin = Benutzer::where('rolle', 'admin')->where('aktiv', true)->firstOrFail();
            $werker = Benutzer::create([
                'personalnummer' => 'DEMO-W', 'vorname' => 'Alex', 'nachname' => 'Beispiel',
                'email' => 'demo.werker@fa-digital.local', 'passwort_hash' => 'WerkerDemo123!',
                'rolle' => 'werker', 'aktiv' => true,
            ]);
            $platz = Arbeitsplatz::create(['nummer' => 'DEMO-AP', 'bezeichnung' => 'Demo-Arbeitsplatz', 'kostenstelle' => 'DEMO', 'stundensatz' => 60, 'aktiv' => true]);
            $artikel = Artikel::create(['artikelnummer' => 'DEMO-WERKER', 'bezeichnung' => 'Demo-Bauteil', 'aktiv' => true]);
            $artikel->positionen()->create(['ag_nummer' => 10, 'bezeichnung' => 'Bauteil bearbeiten', 'arbeitsplatz_id' => $platz->id, 'ruestzeit_min' => 10, 'stueckzeit_min' => 5]);
            $auftrag = app(AuftragAnlegen::class)($admin, [
                'artikel_id' => $artikel->id, 'menge' => 5, 'liefertermin' => now()->addWeek()->toDateString(),
                'kunde' => null, 'bemerkung' => 'Beispielauftrag für das Werker-Terminal',
            ], [], true);
            DB::table('arbeitsgang_zuordnung')->insert(['arbeitsgang_id' => $auftrag->arbeitsgaenge()->firstOrFail()->id, 'benutzer_id' => $werker->id]);
            $this->command->info("Demo-Werker und Auftrag {$auftrag->auftragsnummer} angelegt.");
        });
    }
}
