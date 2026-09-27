<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkPlanStep extends Model
{
    protected $fillable = ['article_id', 'workstation_id', 'sequence', 'name', 'setup_minutes', 'minutes_per_unit'];

    public function workstation(): BelongsTo
    {
        return $this->belongsTo(Workstation::class);
    }
}
