<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Arbeitsgang im Arbeitsplan eines Artikels mit Soll-Rüstzeit und Soll-Stückzeit
 *
 * @property int $id
 * @property int $artikel_id
 * @property int $ag_nummer
 * @property string $bezeichnung
 * @property int $arbeitsplatz_id
 * @property string $ruestzeit_min
 * @property string $stueckzeit_min
 * @property-read Artikel $artikel
 * @property-read Arbeitsplatz $arbeitsplatz
 * @property-read Collection<int, Pruefmerkmal> $pruefmerkmale
 * @property-read Collection<int, ArbeitsplanAenderung> $aenderungen
 */
class ArbeitsplanPosition extends Model
{
    protected $table = 'arbeitsplan_position';

    public $timestamps = false;

    protected $fillable = ['artikel_id', 'ag_nummer', 'bezeichnung', 'arbeitsplatz_id', 'ruestzeit_min', 'stueckzeit_min'];

    /** @return BelongsTo<Artikel, $this> */
    public function artikel(): BelongsTo
    {
        return $this->belongsTo(Artikel::class);
    }

    /** @return BelongsTo<Arbeitsplatz, $this> */
    public function arbeitsplatz(): BelongsTo
    {
        return $this->belongsTo(Arbeitsplatz::class);
    }

    /** @return HasMany<Pruefmerkmal, $this> */
    public function pruefmerkmale(): HasMany
    {
        return $this->hasMany(Pruefmerkmal::class)->orderBy('reihenfolge');
    }

    /** @return HasMany<ArbeitsplanAenderung, $this> */
    public function aenderungen(): HasMany
    {
        return $this->hasMany(ArbeitsplanAenderung::class)->orderByDesc('geaendert_am');
    }
}
