<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\Workstation;
use Livewire\Component;

class Catalog extends Component
{
    public ?int $selectedId = null;
    public string $code = '';
    public string $name = '';
    public string $description = '';
    public int $sequence = 10;
    public string $stepName = '';
    public ?int $workstationId = null;
    public float $setupMinutes = 0;
    public float $minutesPerUnit = 0;

    public function mount(): void
    {
        $this->select(Article::query()->value('id'));
        $this->workstationId = Workstation::query()->value('id');
    }

    public function select(?int $id): void
    {
        $article = $id ? Article::findOrFail($id) : null;
        $this->selectedId = $article?->id;
        $this->code = $article?->code ?? '';
        $this->name = $article?->name ?? '';
        $this->description = $article?->description ?? '';
    }

    public function saveArticle(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        $data = $this->validate([
            'code' => ['required', 'string', 'max:30', 'unique:articles,code,'.$this->selectedId],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        $article = Article::updateOrCreate(['id' => $this->selectedId], $data);
        $this->selectedId = $article->id;
    }

    public function addStep(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        abort_unless($this->selectedId, 422);
        $data = $this->validate([
            'sequence' => ['required', 'integer', 'min:1', 'unique:work_plan_steps,sequence,NULL,id,article_id,'.$this->selectedId],
            'stepName' => ['required', 'string', 'max:255'],
            'workstationId' => ['required', 'exists:workstations,id'],
            'setupMinutes' => ['required', 'numeric', 'min:0'],
            'minutesPerUnit' => ['required', 'numeric', 'min:0'],
        ]);
        Article::findOrFail($this->selectedId)->steps()->create([
            'sequence' => $data['sequence'], 'name' => $data['stepName'],
            'workstation_id' => $data['workstationId'], 'setup_minutes' => $data['setupMinutes'],
            'minutes_per_unit' => $data['minutesPerUnit'],
        ]);
        $this->sequence += 10;
        $this->stepName = '';
    }

    public function removeStep(int $id): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        Article::findOrFail($this->selectedId)->steps()->findOrFail($id)->delete();
    }

    public function render()
    {
        return view('livewire.catalog', [
            'articles' => Article::with('steps.workstation')->orderBy('code')->get(),
            'selected' => $this->selectedId ? Article::with('steps.workstation')->find($this->selectedId) : null,
            'stations' => Workstation::where('active', true)->orderBy('code')->get(),
        ])->layout('layouts::production', ['title' => 'Artikel & Arbeitspläne']);
    }
}
