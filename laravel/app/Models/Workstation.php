<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Workstation extends Model
{
    protected $fillable = ['code', 'name', 'hourly_rate', 'active'];

    protected function casts(): array
    {
        return ['hourly_rate' => 'decimal:2', 'active' => 'boolean'];
    }

    public function activeEntries(): HasManyThrough
    {
        return $this->hasManyThrough(TimeEntry::class, OrderOperation::class, 'workstation_id', 'order_operation_id')
            ->whereNull('time_entries.ended_at');
    }
}
