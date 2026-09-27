<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\ManufacturingOrder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreateOrder extends Component
{
    public ?int $articleId = null;
    public int $quantity = 1;
    public string $dueDate = '';
    public string $customer = '';
    public string $notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        $this->articleId = Article::query()->value('id');
        $this->dueDate = now()->addWeek()->toDateString();
    }

    public function save(bool $release = false)
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        $data = $this->validate([
            'articleId' => ['required', 'exists:articles,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'dueDate' => ['required', 'date'],
            'customer' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $article = Article::with('steps.workstation')->findOrFail($data['articleId']);
        if ($article->steps->isEmpty()) {
            $this->addError('articleId', 'Für diesen Artikel fehlt ein Arbeitsplan.');
            return null;
        }

        $order = DB::transaction(function () use ($data, $article, $release) {
            $order = ManufacturingOrder::create([
                'number' => 'TEMP-'.(string) \Illuminate\Support\Str::uuid(),
                'article_id' => $article->id,
                'quantity' => $data['quantity'],
                'due_date' => $data['dueDate'],
                'customer' => $data['customer'],
                'notes' => $data['notes'],
                'status' => $release ? 'freigegeben' : 'angelegt',
                'created_by' => auth()->id(),
            ]);
            $order->update(['number' => 'FA-'.(1000 + $order->id)]);
            foreach ($article->steps as $step) {
                $order->operations()->create([
                    'workstation_id' => $step->workstation_id,
                    'sequence' => $step->sequence,
                    'name' => $step->name,
                    'planned_setup_minutes' => $step->setup_minutes,
                    'planned_run_minutes' => $step->minutes_per_unit * $data['quantity'],
                    'hourly_rate' => $step->workstation->hourly_rate,
                ]);
            }
            return $order;
        });

        return redirect()->route('orders.show', $order);
    }

    public function render()
    {
        $articles = Article::with('steps.workstation')->orderBy('code')->get();
        $selected = $articles->firstWhere('id', (int) $this->articleId);

        return view('livewire.create-order', compact('articles', 'selected'))->layout('layouts::production', ['title' => 'Auftrag anlegen']);
    }
}
