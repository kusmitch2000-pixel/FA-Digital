<?php

namespace App\Livewire;

use App\Livewire\Concerns\BrauchtRecht;
use App\Models\Arbeitsplatz;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

/** Arbeitsplätze und Stundensätze. Neue Aufträge übernehmen den Stundensatz beim Anlegen. */
class Arbeitsplaetze extends Component
{
    use BrauchtRecht;

    protected string $recht = 'arbeitsplaetze';

    public ?int $ausgewaehlt = null;

    public string $nummer = '';

    public string $bezeichnung = '';

    public string $kostenstelle = '';

    public ?float $stundensatz = null;

    public bool $aktiv = true;

    public function bearbeiten(?int $id): void
    {
        $platz = $id ? Arbeitsplatz::findOrFail($id) : null;

        $this->resetValidation();
        $this->ausgewaehlt = $platz?->id;
        $this->nummer = $platz->nummer ?? '';
        $this->bezeichnung = $platz->bezeichnung ?? '';
        $this->kostenstelle = $platz->kostenstelle ?? '';
        $this->stundensatz = $platz ? (float) $platz->stundensatz : null;
        $this->aktiv = $platz->aktiv ?? true;
    }

    public function speichern(): void
    {
        $this->resetErrorBag();
        $daten = $this->validate([
            'nummer' => ['required', 'string', 'max:10', Rule::unique('arbeitsplatz', 'nummer')->ignore($this->ausgewaehlt)],
            'bezeichnung' => ['required', 'string', 'max:80'],
            'kostenstelle' => ['required', 'string', 'max:10'],
            'stundensatz' => ['required', 'numeric', 'min:0', 'max:999999'],
            'aktiv' => ['boolean'],
        ]);

        $platz = Arbeitsplatz::updateOrCreate(['id' => $this->ausgewaehlt], $daten);
        $this->ausgewaehlt = $platz->id;

        session()->flash('status', "Arbeitsplatz {$platz->nummer} gespeichert.");
    }

    public function render(): View
    {
        return view('livewire.arbeitsplaetze', ['plaetze' => Arbeitsplatz::orderBy('nummer')->get()])
            ->layout('layouts::production', ['title' => 'Arbeitsplätze']);
    }
}
