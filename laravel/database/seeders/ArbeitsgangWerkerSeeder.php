<?php

namespace Database\Seeders;

use App\Models\Benutzer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Legt lokale Entwicklungskonten an und ordnet sie bestehenden offenen Arbeitsgängen zu. */
class ArbeitsgangWerkerSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Tätigkeitskonten sind nur für lokale Entwicklung und Tests vorgesehen.');
        }

        DB::transaction(function () {
            foreach (config('arbeitsgang_werker') as $gruppe) {
                $vorhanden = Benutzer::where('personalnummer', $gruppe['personalnummer'])
                    ->orWhere('email', $gruppe['email'])->lockForUpdate()->get();
                if ($vorhanden->count() > 1 || ($vorhanden->count() === 1 && (
                    $vorhanden[0]->personalnummer !== $gruppe['personalnummer']
                    || $vorhanden[0]->email !== $gruppe['email']
                    || $vorhanden[0]->rolle !== 'werker'
                    || ! $vorhanden[0]->aktiv
                ))) {
                    throw new RuntimeException('Ein Tätigkeitskonto ist bereits anders vergeben oder gesperrt: '.$gruppe['email']);
                }
                $werker = $vorhanden->first() ?? Benutzer::create([
                    'personalnummer' => $gruppe['personalnummer'], 'vorname' => 'Fachkraft',
                    'nachname' => $gruppe['taetigkeit'], 'email' => $gruppe['email'],
                    'passwort_hash' => 'fa-digital', 'rolle' => 'werker', 'aktiv' => true,
                ]);
                $arbeitsgaenge = DB::table('arbeitsgang as ag')
                    ->join('fertigungsauftrag as fa', 'fa.id', '=', 'ag.fertigungsauftrag_id')
                    ->where('ag.status', 'offen')
                    ->whereIn('fa.status', ['angelegt', 'freigegeben', 'in_arbeit'])
                    ->whereIn('ag.bezeichnung', $gruppe['arbeitsgaenge'])
                    ->pluck('ag.id');
                foreach ($arbeitsgaenge as $id) {
                    DB::table('arbeitsgang_zuordnung')->insertOrIgnore([
                        'arbeitsgang_id' => $id, 'benutzer_id' => $werker->id,
                    ]);
                }
            }
        });
    }
}
