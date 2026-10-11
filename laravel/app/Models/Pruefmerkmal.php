<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vorlage für einen Prüfpunkt. Ohne Zertifikatsart wird immer geprüft.
 *
 * @property int $id
 * @property int $arbeitsplan_position_id
 * @property int $reihenfolge
 * @property string $bezeichnung
 * @property 'sichtpruefung'|'messwert' $art
 * @property string|null $sollwert
 * @property string|null $untere_grenze
 * @property string|null $obere_grenze
 * @property string|null $einheit
 * @property int|null $zertifikatsart_id
 * @property-read Zertifikatsart|null $zertifikatsart
 */
class Pruefmerkmal extends Model
{
    protected $table = 'pruefmerkmal';

    public $timestamps = false;

    protected $fillable = [
        'arbeitsplan_position_id', 'reihenfolge', 'bezeichnung', 'art', 'sollwert',
        'untere_grenze', 'obere_grenze', 'einheit', 'zertifikatsart_id',
    ];

    /** @return BelongsTo<Zertifikatsart, $this> */
    public function zertifikatsart(): BelongsTo
    {
        return $this->belongsTo(Zertifikatsart::class);
    }
}
