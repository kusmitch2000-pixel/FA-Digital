<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historie der übernommenen Soll-Zeiten mit altem und neuem Wert
 *
 * @property int $id
 * @property int $arbeitsplan_position_id
 * @property 'ruestzeit'|'stueckzeit' $feld
 * @property string $alter_wert
 * @property string $neuer_wert
 * @property string $begruendung
 * @property int $geaendert_von
 * @property CarbonImmutable $geaendert_am
 * @property-read Benutzer $geaendertVon
 */
class ArbeitsplanAenderung extends Model
{
    protected $table = 'arbeitsplan_aenderung';

    public $timestamps = false;

    protected $fillable = ['arbeitsplan_position_id', 'feld', 'alter_wert', 'neuer_wert', 'begruendung', 'geaendert_von', 'geaendert_am'];

    protected function casts(): array
    {
        return ['geaendert_am' => 'datetime'];
    }

    /** @return BelongsTo<Benutzer, $this> */
    public function geaendertVon(): BelongsTo
    {
        return $this->belongsTo(Benutzer::class, 'geaendert_von');
    }
}
