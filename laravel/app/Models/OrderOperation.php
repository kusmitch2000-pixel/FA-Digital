<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderOperation extends Model
{
    protected $fillable = ['manufacturing_order_id', 'workstation_id', 'sequence', 'name', 'planned_setup_minutes', 'planned_run_minutes', 'hourly_rate', 'status', 'good_quantity', 'scrap_quantity', 'notes'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class, 'manufacturing_order_id');
    }

    public function workstation(): BelongsTo
    {
        return $this->belongsTo(Workstation::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function getPlannedMinutesAttribute(): float
    {
        return (float) $this->planned_setup_minutes + (float) $this->planned_run_minutes;
    }

    public function getActualMinutesAttribute(): float
    {
        return round($this->entries->where('kind', '!=', 'interruption')->sum(fn (TimeEntry $entry) => $entry->minutes), 2);
    }
}
