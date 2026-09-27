<?php

namespace App\Livewire;

use App\Models\OrderOperation;
use App\Models\TimeEntry;
use App\Models\Workstation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Terminal extends Component
{
    public ?int $workstationId = null;
    public string $reason = '';
    public int $goodQuantity = 0;
    public int $scrapQuantity = 0;
    public string $notes = '';

    public function mount(): void
    {
        $this->workstationId = Workstation::query()->value('id');
    }

    public function start(int $id, string $kind): void
    {
        abort_unless(in_array($kind, ['setup', 'run'], true), 422);
        DB::transaction(function () use ($id, $kind) {
            $operation = OrderOperation::with('order')->lockForUpdate()->findOrFail($id);
            abort_unless($operation->workstation_id === (int) $this->workstationId && in_array($operation->order->status, ['freigegeben', 'in_arbeit'], true), 403);
            abort_unless($operation->status !== 'fertig', 409);
            $earlierOpen = OrderOperation::where('manufacturing_order_id', $operation->manufacturing_order_id)
                ->where('sequence', '<', $operation->sequence)->where('status', '!=', 'fertig')->exists();
            if ($earlierOpen) {
                throw ValidationException::withMessages(['operation' => 'Der vorherige Arbeitsgang ist noch nicht fertig.']);
            }
            $otherActive = TimeEntry::where('user_id', auth()->id())->whereNull('ended_at')->exists();
            $operationActive = $operation->entries()->whereNull('ended_at')->exists();
            if ($otherActive || $operationActive) {
                throw ValidationException::withMessages(['operation' => 'Es läuft bereits eine Buchung.']);
            }
            $operation->entries()->create(['user_id' => auth()->id(), 'kind' => $kind, 'started_at' => now()]);
            $operation->update(['status' => 'in_arbeit']);
            $operation->order->update(['status' => 'in_arbeit']);
        });
    }

    public function switchKind(string $kind): void
    {
        abort_unless(in_array($kind, ['setup', 'run'], true), 422);
        DB::transaction(function () use ($kind) {
            $entry = TimeEntry::where('user_id', auth()->id())->whereNull('ended_at')->lockForUpdate()->firstOrFail();
            abort_unless($entry->kind !== 'interruption', 409);
            $entry->update(['ended_at' => now()]);
            $entry->operation->entries()->create(['user_id' => auth()->id(), 'kind' => $kind, 'started_at' => now()]);
        });
    }

    public function pause(): void
    {
        $this->validate(['reason' => 'required|in:pause,material,stoerung,werkzeug,sonstiges']);
        DB::transaction(function () {
            $entry = TimeEntry::where('user_id', auth()->id())->whereNull('ended_at')->lockForUpdate()->firstOrFail();
            abort_unless($entry->kind !== 'interruption', 409);
            $entry->update(['ended_at' => now()]);
            $entry->operation->entries()->create(['user_id' => auth()->id(), 'kind' => 'interruption', 'reason' => $this->reason, 'resume_kind' => $entry->kind, 'started_at' => now()]);
        });
        $this->reason = '';
    }

    public function resume(): void
    {
        DB::transaction(function () {
            $entry = TimeEntry::where('user_id', auth()->id())->whereNull('ended_at')->lockForUpdate()->firstOrFail();
            abort_unless($entry->kind === 'interruption', 409);
            $entry->update(['ended_at' => now()]);
            $entry->operation->entries()->create(['user_id' => auth()->id(), 'kind' => $entry->resume_kind === 'setup' ? 'setup' : 'run', 'started_at' => now()]);
        });
    }

    public function finish(): void
    {
        $this->validate([
            'goodQuantity' => 'required|integer|min:0',
            'scrapQuantity' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);
        DB::transaction(function () {
            $entry = TimeEntry::where('user_id', auth()->id())->whereNull('ended_at')->lockForUpdate()->firstOrFail();
            abort_unless($entry->kind !== 'interruption', 409);
            $entry->update(['ended_at' => now()]);
            $operation = $entry->operation;
            if ($this->goodQuantity + $this->scrapQuantity > $operation->order->quantity) {
                throw ValidationException::withMessages(['goodQuantity' => 'Die Gesamtmenge überschreitet die Auftragsmenge.']);
            }
            $operation->update([
                'status' => 'fertig', 'good_quantity' => $this->goodQuantity,
                'scrap_quantity' => $this->scrapQuantity, 'notes' => $this->notes,
            ]);
            if (! $operation->order->operations()->where('status', '!=', 'fertig')->exists()) {
                $operation->order->update(['status' => 'fertig']);
            }
        });
        $this->reset('goodQuantity', 'scrapQuantity', 'notes');
    }

    public function render()
    {
        $stations = Workstation::where('active', true)->orderBy('code')->get();
        $active = TimeEntry::with(['operation.order.article', 'operation.workstation'])
            ->where('user_id', auth()->id())->whereNull('ended_at')->first();
        $queue = OrderOperation::with(['order.article', 'entries'])
            ->where('workstation_id', $this->workstationId)
            ->where('status', '!=', 'fertig')
            ->whereHas('order', fn ($query) => $query->whereIn('status', ['freigegeben', 'in_arbeit']))
            ->orderBy('created_at')->get();

        return view('livewire.terminal', compact('stations', 'active', 'queue'))->layout('layouts::production', ['title' => 'Werker-Terminal']);
    }
}
