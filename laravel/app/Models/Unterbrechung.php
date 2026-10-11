<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Unterbrechung mit Grund. Zählt nicht zur Ist-Zeit des Arbeitsgangs.
 *
 * @property int $id
 * @property int $arbeitsgang_id
 * @property int $benutzer_id
 * @property int $unterbrechungsgrund_id
 * @property CarbonImmutable $beginn
 * @property CarbonImmutable|null $ende
 * @property string|null $bemerkung
 * @property-read Unterbrechungsgrund $grund
 * @property-read Benutzer $benutzer
 */
class Unterbrechung extends Model
{
    protected $table = 'unterbrechung';

    public $timestamps = false;

    protected $fillable = ['arbeitsgang_id', 'benutzer_id', 'unterbrechungsgrund_id', 'beginn', 'ende', 'bemerkung'];

    protected function casts(): array
    {
        return ['beginn' => 'datetime', 'ende' => 'datetime'];
    }

    /** @return BelongsTo<Unterbrechungsgrund, $this> */
    public function grund(): BelongsTo
    {
        return $this->belongsTo(Unterbrechungsgrund::class, 'unterbrechungsgrund_id');
    }

    /** @return BelongsTo<Benutzer, $this> */
    public function benutzer(): BelongsTo
    {
        return $this->belongsTo(Benutzer::class);
    }
}
