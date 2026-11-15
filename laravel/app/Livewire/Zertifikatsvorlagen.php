<?php

namespace App\Livewire;

use App\Actions\Fertigung\Fachfehler;
use App\Livewire\Concerns\BrauchtRecht;
use App\Models\Zertifikatsart;
use App\Support\Zertifikate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Zertifikatsvorlagen: welche Zertifikate ein Kunde fordern kann. Neben den Zertifikaten nach
 * DIN EN 10204 legt die Arbeitsvorbereitung eigene an, z. B. ein Lackprüfprotokoll. Für welche Artikel
 * ein Zertifikat gilt, ergibt sich aus seinen Prüfmerkmalen im Arbeitsplan (siehe App\Support\Zertifikate).
 */
class Zertifikatsvorlagen extends Component
{
    use BrauchtRecht;

    protected string $recht = 'zertifikatsvorlagen';

    /** null = neues Zertifikat, sonst die ID des bearbeiteten */
    public ?int $bearbeitetId = null;

    public string $kuerzel = '';

    public string $bezeichnung = '';

    public string $beschreibung = '';

    public string $meldung = '';

    public function bearbeiten(?int $id): void
    {
        $this->pruefeRecht('zertifikatsvorlage.anlegen');
        $this->resetErrorBag();
        $this->meldung = '';
        $zertifikat = $id === null ? null : Zertifikatsart::findOrFail($id);

        if ($zertifikat && Zertifikate::istNorm($zertifikat)) {
            $this->addError('aktion', 'Zertifikate nach DIN EN 10204 sind fest vorgegeben.');

            return;
        }

        $this->bearbeitetId = $zertifikat?->id;
        $this->kuerzel = $zertifikat->kuerzel ?? '';
        $this->bezeichnung = $zertifikat->bezeichnung ?? '';
        $this->beschreibung = $zertifikat->beschreibung ?? '';
    }

    public function speichern(): void
    {
        $this->pruefeRecht('zertifikatsvorlage.anlegen');
        $this->resetErrorBag();
        $this->kuerzel = mb_strtoupper(trim($this->kuerzel));
        $daten = $this->validate([
            'kuerzel' => ['required', 'string', 'max:10', Rule::unique('zertifikatsart', 'kuerzel')->ignore($this->bearbeitetId), Rule::notIn(Zertifikate::NORM)],
            'bezeichnung' => ['required', 'string', 'max:80'],
            'beschreibung' => ['nullable', 'string', 'max:255'],
        ]);

        if ($this->bearbeitetId) {
            Zertifikatsart::findOrFail($this->bearbeitetId)->update($daten);
            $this->meldung = "Zertifikat {$daten['kuerzel']} gespeichert.";
        } else {
            Zertifikatsart::create($daten);
            $this->meldung = "Zertifikat {$daten['kuerzel']} angelegt. Ohne Prüfmerkmale gilt es für alle Artikel. "
                .'Unter „Artikel & Arbeitspläne“ beim passenden Arbeitsgang ein Prüfmerkmal mit diesem Zertifikat anlegen, dann gilt es nur für diese Artikel.';
        }

        $this->reset('bearbeitetId', 'kuerzel', 'bezeichnung', 'beschreibung');
    }

    public function loeschen(int $id): void
    {
        $this->pruefeRecht('zertifikatsvorlage.anlegen');
        $this->resetErrorBag();
        $this->meldung = '';
        $zertifikat = Zertifikatsart::findOrFail($id);

        if (Zertifikate::istNorm($zertifikat)) {
            Fachfehler::melden('Zertifikate nach DIN EN 10204 lassen sich nicht löschen.');
        }

        $auftraege = DB::table('auftrag_zertifikat')->join('fertigungsauftrag', 'fertigungsauftrag.id', '=', 'auftrag_zertifikat.fertigungsauftrag_id')
            ->where('zertifikatsart_id', $id)->orderBy('auftragsnummer')->pluck('auftragsnummer');
        if ($auftraege->isNotEmpty()) {
            $this->addError('aktion', "{$zertifikat->kuerzel} wird in {$auftraege->join(', ')} gefordert und bleibt deshalb.");

            return;
        }

        if (DB::table('pruefmerkmal')->where('zertifikatsart_id', $id)->exists()) {
            $this->addError('aktion', "{$zertifikat->kuerzel} hat noch Prüfmerkmale im Arbeitsplan. Bitte diese zuerst unter „Artikel & Arbeitspläne“ entfernen.");

            return;
        }

        $zertifikat->delete();
        $this->meldung = "Zertifikat {$zertifikat->kuerzel} {$zertifikat->bezeichnung} wurde gelöscht.";
        if ($this->bearbeitetId === $id) {
            $this->reset('bearbeitetId', 'kuerzel', 'bezeichnung', 'beschreibung');
        }
    }

    public function render(): View
    {
        $merkmale = DB::table('pruefmerkmal')
            ->join('arbeitsplan_position', 'arbeitsplan_position.id', '=', 'pruefmerkmal.arbeitsplan_position_id')
            ->join('artikel', 'artikel.id', '=', 'arbeitsplan_position.artikel_id')
            ->whereNotNull('pruefmerkmal.zertifikatsart_id')
            ->orderBy('artikel.artikelnummer')->orderBy('arbeitsplan_position.ag_nummer')->orderBy('pruefmerkmal.reihenfolge')
            ->get(['pruefmerkmal.zertifikatsart_id', 'pruefmerkmal.bezeichnung', 'artikel.artikelnummer', 'arbeitsplan_position.ag_nummer'])
            ->groupBy('zertifikatsart_id');

        return view('livewire.zertifikatsvorlagen', [
            'zertifikate' => Zertifikatsart::orderBy('kuerzel')->get(),
            'merkmale' => $merkmale,
            'verwendet' => DB::table('auftrag_zertifikat')->selectRaw('zertifikatsart_id, COUNT(*) AS anzahl')->groupBy('zertifikatsart_id')->pluck('anzahl', 'zertifikatsart_id'),
            'darfAendern' => $this->darf('zertifikatsvorlage.anlegen'),
        ])->layout('layouts::production', ['title' => 'Zertifikatsvorlagen']);
    }
}
