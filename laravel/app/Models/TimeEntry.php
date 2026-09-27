<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    protected $fillable = ['order_operation_id', 'user_id', 'kind', 'reason', 'resume_kind', 'started_at', 'ended_at', 'notes'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(OrderOperation::class, 'order_operation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getMinutesAttribute(): float
    {
        return $this->ended_at ? round($this->started_at->diffInSeconds($this->ended_at) / 60, 2) : 0;
    }
}
