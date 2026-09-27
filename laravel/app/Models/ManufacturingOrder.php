<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManufacturingOrder extends Model
{
    protected $fillable = ['number', 'article_id', 'quantity', 'due_date', 'customer', 'notes', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(OrderOperation::class)->orderBy('sequence');
    }
}
