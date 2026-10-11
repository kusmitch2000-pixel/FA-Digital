<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Arbeitsgang eines Auftrags. Soll-Zeiten und Stundensatz sind eine Kopie aus dem Arbeitsplan.
 *
 * @property int $id
 * @property int $fertigungsauftrag_id
 * @property int|null $arbeitsplan_position_id
 * @property int $ag_nummer
 * @property string $bezeichnung
 * @property int $arbeitsplatz_id
 * @property string $soll_ruestzeit_min
 * @property string $soll_stueckzeit_min
 * @property string $stundensatz
 * @property 'offen'|'laeuft'|'unterbrochen'|'erledigt' $status
 * @property int|null $gutmenge
 * @property int|null $ausschuss
 * @property int|null $fertig_gemeldet_von
 * @property CarbonImmutable|null $fertig_gemeldet_am
 * @property string|null $bemerkung
 * @property-read Fertigungsauftrag $auftrag
 * @property-read Arbeitsplatz $arbeitsplatz
 * @property-read Collection<int, Zeitbuchung> $zeitbuchungen
 * @property-read Collection<int, Unterbrechung> $unterbrechungen
 * @property-read Collection<int, Pruefpunkt> $pruefpunkte
 */
class Arbeitsgang extends Model
{
    protected $table = 'arbeitsgang';

    public $timestamps = false;

    protected $fillable = [
        'fertigungsauftrag_id', 'arbeitsplan_position_id', 'ag_nummer', 'bezeichnung', 'arbeitsplatz_id',
        'soll_ruestzeit_min', 'soll_stueckzeit_min', 'stundensatz', 'status', 'gutmenge', 'ausschuss',
        'fertig_gemeldet_von', 'fertig_gemeldet_am', 'bemerkung',
    ];

    protected function casts(): array
    {
        return ['fertig_gemeldet_am' => 'datetime'];
    }

    /** @return BelongsTo<Fertigungsauftrag, $this> */
    public function auftrag(): BelongsTo
    {
        return $this->belongsTo(Fertigungsauftrag::class, 'fertigungsauftrag_id');
    }

    /** @return BelongsTo<Arbeitsplatz, $this> */
    public function arbeitsplatz(): BelongsTo
    {
        return $this->belongsTo(Arbeitsplatz::class);
    }

    /** @return HasMany<Zeitbuchung, $this> */
    public function zeitbuchungen(): HasMany
    {
        return $this->hasMany(Zeitbuchung::class)->orderBy('beginn');
    }

    /** @return HasMany<Unterbrechung, $this> */
    public function unterbrechungen(): HasMany
    {
        return $this->hasMany(Unterbrechung::class)->orderBy('beginn');
    }

    /** @return HasMany<Pruefpunkt, $this> */
    public function pruefpunkte(): HasMany
    {
        return $this->hasMany(Pruefpunkt::class)->orderBy('reihenfolge');
    }
}
