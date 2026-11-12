<?php

namespace App\Livewire;

use App\Actions\Fertigung\Werkstatt;
use App\Livewire\Concerns\BrauchtRecht;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Messung extends Component
{
    use BrauchtRecht;

    protected string $recht = 'terminal';

    #[Locked]
    public int $arbeitsgangId;

    /** @var array<int, string> */
    public array $istwert = [];

    /** @var array<int, string> */
    public array $bemerkung = [];

    public function mount(int $arbeitsgang): void
    {
        $this->arbeitsgangId = $arbeitsgang;
        app(Werkstatt::class)->messArbeitsgang(auth()->user(), $arbeitsgang);
    }

    public function speichern(int $punktId, ?string $ergebnis = null): void
    {
        $this->resetErrorBag();
        app(Werkstatt::class)->messen(auth()->user(), $this->arbeitsgangId, $punktId, $this->istwert[$punktId] ?? null, $ergebnis, $this->bemerkung[$punktId] ?? null);
    }

    public function abschliessen(): void
    {
        $this->resetErrorBag();
        app(Werkstatt::class)->messenAbschliessen(auth()->user(), $this->arbeitsgangId);
        session()->flash('status', 'Messung abgeschlossen. Der Arbeitsgang ist fertig.');
        $this->redirectRoute('terminal', navigate: false);
    }

    public function render(): View
    {
        $arbeitsgang = app(Werkstatt::class)->messArbeitsgang(auth()->user(), $this->arbeitsgangId);
        $arbeitsgang->load('pruefpunkte');

        return view('livewire.messung', ['arbeitsgang' => $arbeitsgang])
            ->layout('layouts::production', ['title' => 'Messung']);
    }
}
