<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    protected $fillable = ['code', 'name', 'description'];

    public function steps(): HasMany
    {
        return $this->hasMany(WorkPlanStep::class)->orderBy('sequence');
    }
}
