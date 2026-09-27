<?php

namespace App\Livewire;

use App\Models\ManufacturingOrder;
use Livewire\Component;

class Orders extends Component
{
    public string $search = '';
    public string $status = '';

    public function render()
    {
        $orders = ManufacturingOrder::with(['article', 'operations.entries'])
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('number', 'like', '%'.$this->search.'%')
                    ->orWhereHas('article', fn ($article) => $article->where('code', 'like', '%'.$this->search.'%')->orWhere('name', 'like', '%'.$this->search.'%'));
            }))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest()->get();

        return view('livewire.orders', compact('orders'))->layout('layouts::production', ['title' => 'Aufträge']);
    }
}
