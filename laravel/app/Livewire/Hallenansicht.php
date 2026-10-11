<?php

namespace App\Livewire;

use App\Livewire\Concerns\BrauchtRecht;
use App\Models\Unterbrechungsgrund;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Hallenansicht (Anwendungsfall 7): eine Karte je Arbeitsplatz mit Ampel für die verbrauchte Soll-Zeit,
 * darunter die schlanke Meister-Ansicht der Aufträge in der Werkstatt (Rückmeldung vom 01.10.2026).
 * Die Seite lädt sich alle 30 Sekunden selbst neu.
 */
class Hallenansicht extends Component
{
    use BrauchtRecht;

    protected string $recht = 'halle';

    public function render(): View
    {
        $plaetze = DB::table('v_hallenansicht')->orderBy('arbeitsplatz_nummer')->get();

        // Für freie Arbeitsplätze: was als Nächstes kommt
        $naechste = DB::table('v_arbeitsvorrat')
            ->whereIn('bereitschaft', ['bereit', 'wartet'])
            ->orderByRaw("bereitschaft = 'bereit' DESC")
            ->orderBy('liefertermin')
            ->orderBy('auftragsnummer')
            ->get()
            ->groupBy('arbeitsplatz_id')
            ->map->first();

        $auftraege = DB::table('v_meister_auftraege')->orderByDesc('verzug')->orderBy('liefertermin')->get();
        $buchungen = DB::table('v_letzte_buchungen')->orderByDesc('zeitpunkt')->limit(8)->get();
        $geplant = Unterbrechungsgrund::pluck('geplant', 'bezeichnung');

        return view('livewire.hallenansicht', [
            'plaetze' => $plaetze,
            'naechste' => $naechste,
            'auftraege' => $auftraege,
            'buchungen' => $buchungen,
            'handlungsbedarf' => $this->handlungsbedarf($plaetze, $auftraege, $geplant),
            'anzahl' => DB::table('fertigungsauftrag')->selectRaw('status, COUNT(*) AS anzahl')->groupBy('status')->pluck('anzahl', 'status'),
        ])->layout('layouts::production', ['title' => 'Hallenansicht']);
    }

    /**
     * Kurze Liste, wo der Meister eingreifen sollte
     *
     * @param  Collection<int, \stdClass>  $plaetze  Zeilen aus v_hallenansicht
     * @param  Collection<int, \stdClass>  $auftraege  Zeilen aus v_meister_auftraege
     * @param  Collection<string, bool>  $geplant  Unterbrechungsgrund → geplant
     * @return list<array{auftrag: string, text: string}>
     */
    private function handlungsbedarf(Collection $plaetze, Collection $auftraege, Collection $geplant): array
    {
        $punkte = [];

        foreach ($plaetze as $platz) {
            if ($platz->zustand === 'belegt' && $platz->ampel === 'rot') {
                $punkte[] = ['auftrag' => $platz->auftragsnummer, 'text' => "{$platz->arbeitsplatz}: AG {$platz->ag_nummer} hat die Soll-Zeit überschritten ("
                    .zahl($platz->ist_zeit_min).' von '.minuten($platz->soll_zeit_min).') und ist noch nicht fertig gemeldet.'];
            }

            if ($platz->zustand === 'unterbrochen' && ! ($geplant[$platz->unterbrechungsgrund] ?? false)) {
                $punkte[] = ['auftrag' => $platz->auftragsnummer, 'text' => "{$platz->arbeitsplatz}: steht seit ".uhrzeit($platz->unterbrochen_seit)
                    ." ({$platz->unterbrochen_min} min) wegen {$platz->unterbrechungsgrund}."];
            }
        }

        foreach ($auftraege as $auftrag) {
            if ($auftrag->verzug) {
                $punkte[] = ['auftrag' => $auftrag->auftragsnummer, 'text' => 'Liefertermin '.datum($auftrag->liefertermin).' ist überschritten.'];
            }

            if ($auftrag->pruefpunkte_nicht_in_ordnung > 0) {
                $punkte[] = ['auftrag' => $auftrag->auftragsnummer, 'text' => "{$auftrag->pruefpunkte_nicht_in_ordnung} Prüfpunkt(e) nicht in Ordnung, Nacharbeit prüfen."];
            }
        }

        return $punkte;
    }
}
