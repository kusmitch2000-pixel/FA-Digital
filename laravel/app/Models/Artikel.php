<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $artikelnummer
 * @property string $bezeichnung
 * @property bool $aktiv
 * @property-read Collection<int, ArbeitsplanPosition> $positionen
 */
class Artikel extends Model
{
    protected $table = 'artikel';

    public $timestamps = false;

    protected $fillable = ['artikelnummer', 'bezeichnung', 'aktiv'];

    protected function casts(): array
    {
        return ['aktiv' => 'boolean'];
    }

    /** @return HasMany<ArbeitsplanPosition, $this> */
    public function positionen(): HasMany
    {
        return $this->hasMany(ArbeitsplanPosition::class)->orderBy('ag_nummer');
    }
}
