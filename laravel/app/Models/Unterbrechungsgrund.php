<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $bezeichnung
 * @property bool $geplant Pause = geplant, zählt nicht als Zeitverlust
 * @property bool $aktiv
 */
class Unterbrechungsgrund extends Model
{
    protected $table = 'unterbrechungsgrund';

    public $timestamps = false;

    protected $fillable = ['bezeichnung', 'geplant', 'aktiv'];

    protected function casts(): array
    {
        return ['geplant' => 'boolean', 'aktiv' => 'boolean'];
    }
}
