<?php

namespace App\Livewire;

use App\Models\Workstation;
use Livewire\Component;

class Workstations extends Component
{
    public ?int $selectedId = null;
    public string $code = '';
    public string $name = '';
    public float $hourlyRate = 0;
    public bool $active = true;

    public function select(?int $id): void
    {
        $station = $id ? Workstation::findOrFail($id) : null;
        $this->selectedId = $station?->id;
        $this->code = $station?->code ?? '';
        $this->name = $station?->name ?? '';
        $this->hourlyRate = (float) ($station?->hourly_rate ?? 0);
        $this->active = $station?->active ?? true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        $data = $this->validate([
            'code' => ['required', 'string', 'max:30', 'unique:workstations,code,'.$this->selectedId],
            'name' => ['required', 'string', 'max:255'],
            'hourlyRate' => ['required', 'numeric', 'min:0'],
            'active' => ['boolean'],
        ]);
        $station = Workstation::updateOrCreate(['id' => $this->selectedId], [
            'code' => $data['code'], 'name' => $data['name'],
            'hourly_rate' => $data['hourlyRate'], 'active' => $data['active'],
        ]);
        $this->selectedId = $station->id;
    }

    public function render()
    {
        return view('livewire.workstations', ['stations' => Workstation::orderBy('code')->get()])
            ->layout('layouts::production', ['title' => 'Arbeitsplätze']);
    }
}
