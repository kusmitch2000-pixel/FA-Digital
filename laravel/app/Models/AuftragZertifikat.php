<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Werkszertifikat, das der Kunde für einen Auftrag fordert
 *
 * @property int $id
 * @property int $fertigungsauftrag_id
 * @property int $zertifikatsart_id
 * @property 'gefordert'|'erstellt'|'freigegeben' $status
 * @property CarbonImmutable|null $erstellt_am
 * @property int|null $freigegeben_von
 * @property CarbonImmutable|null $freigegeben_am
 * @property string|null $dateiname
 * @property-read Fertigungsauftrag $auftrag
 * @property-read Zertifikatsart $zertifikatsart
 * @property-read Benutzer|null $freigegebenVon
 */
class AuftragZertifikat extends Model
{
    protected $table = 'auftrag_zertifikat';

    public $timestamps = false;

    protected $fillable = ['fertigungsauftrag_id', 'zertifikatsart_id', 'status', 'erstellt_am', 'freigegeben_von', 'freigegeben_am', 'dateiname'];

    protected function casts(): array
    {
        return ['erstellt_am' => 'datetime', 'freigegeben_am' => 'datetime'];
    }

    /** @return BelongsTo<Fertigungsauftrag, $this> */
    public function auftrag(): BelongsTo
    {
        return $this->belongsTo(Fertigungsauftrag::class, 'fertigungsauftrag_id');
    }

    /** @return BelongsTo<Zertifikatsart, $this> */
    public function zertifikatsart(): BelongsTo
    {
        return $this->belongsTo(Zertifikatsart::class);
    }

    /** @return BelongsTo<Benutzer, $this> */
    public function freigegebenVon(): BelongsTo
    {
        return $this->belongsTo(Benutzer::class, 'freigegeben_von');
    }
}
