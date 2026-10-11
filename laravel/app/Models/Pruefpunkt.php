<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Prüfpunkt der Checkliste eines Arbeitsgangs mit dem Ergebnis des Werkers
 *
 * @property int $id
 * @property int $arbeitsgang_id
 * @property int|null $pruefmerkmal_id
 * @property int $reihenfolge
 * @property string $bezeichnung
 * @property 'sichtpruefung'|'messwert' $art
 * @property string|null $sollwert
 * @property string|null $untere_grenze
 * @property string|null $obere_grenze
 * @property string|null $einheit
 * @property string|null $istwert
 * @property 'offen'|'in_ordnung'|'nicht_in_ordnung' $ergebnis
 * @property string|null $bemerkung
 * @property int|null $geprueft_von
 * @property CarbonImmutable|null $geprueft_am
 * @property-read Arbeitsgang $arbeitsgang
 */
class Pruefpunkt extends Model
{
    protected $table = 'pruefpunkt';

    public $timestamps = false;

    protected $fillable = [
        'arbeitsgang_id', 'pruefmerkmal_id', 'reihenfolge', 'bezeichnung', 'art', 'sollwert', 'untere_grenze',
        'obere_grenze', 'einheit', 'istwert', 'ergebnis', 'bemerkung', 'geprueft_von', 'geprueft_am',
    ];

    protected function casts(): array
    {
        return ['geprueft_am' => 'datetime'];
    }

    /** @return BelongsTo<Arbeitsgang, $this> */
    public function arbeitsgang(): BelongsTo
    {
        return $this->belongsTo(Arbeitsgang::class);
    }
}
