<?php

namespace App\Livewire;

use App\Models\ManufacturingOrder;
use App\Models\OrderOperation;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Livewire\Component;

class OrderDetail extends Component
{
    public ManufacturingOrder $order;
    public ?int $operationId = null;
    public string $kind = 'run';
    public string $startedAt = '';
    public string $endedAt = '';
    public string $notes = '';

    public function mount(ManufacturingOrder $order): void
    {
        $this->order = $order;
    }

    public function setStatus(string $status): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        abort_unless(in_array($status, ['freigegeben', 'storniert'], true), 422);
        abort_unless($this->order->status === 'angelegt', 409);
        $this->order->update(['status' => $status]);
    }

    public function addEntry(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        $data = $this->validate([
            'operationId' => ['required', 'exists:order_operations,id'],
            'kind' => ['required', 'in:setup,run'],
            'startedAt' => ['required', 'date'],
            'endedAt' => ['required', 'date', 'after:startedAt'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $operation = OrderOperation::where('manufacturing_order_id', $this->order->id)->findOrFail($data['operationId']);
        TimeEntry::create([
            'order_operation_id' => $operation->id,
            'user_id' => auth()->id(),
            'kind' => $data['kind'],
            'started_at' => Carbon::parse($data['startedAt']),
            'ended_at' => Carbon::parse($data['endedAt']),
            'notes' => $data['notes'],
        ]);
        $this->reset('startedAt', 'endedAt', 'notes');
        session()->flash('status', 'Zeitbuchung gespeichert.');
    }

    public function render()
    {
        $this->order->load(['article', 'operations.workstation', 'operations.entries.user']);
        return view('livewire.order-detail')->layout('layouts::production', ['title' => $this->order->number]);
    }
}
