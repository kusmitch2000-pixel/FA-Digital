<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rüsten oder Bearbeiten. ende = null heißt: läuft noch.
 * Buchungen werden nie gelöscht. Eine Korrektur legt eine neue Buchung an und setzt die alte auf gueltig = 0.
 *
 * @property int $id
 * @property int $arbeitsgang_id
 * @property int $benutzer_id
 * @property 'ruesten'|'bearbeiten' $taetigkeit
 * @property CarbonImmutable $beginn
 * @property CarbonImmutable|null $ende
 * @property 'terminal'|'nachtrag'|'korrektur' $quelle
 * @property bool $gueltig
 * @property int|null $ersetzt_buchung_id
 * @property string|null $begruendung
 * @property int $erfasst_von
 * @property CarbonImmutable $erfasst_am
 * @property-read Arbeitsgang $arbeitsgang
 * @property-read Benutzer $benutzer
 * @property-read Benutzer $erfasstVon
 */
class Zeitbuchung extends Model
{
    protected $table = 'zeitbuchung';

    public $timestamps = false;

    protected $fillable = [
        'arbeitsgang_id', 'benutzer_id', 'taetigkeit', 'beginn', 'ende', 'quelle', 'gueltig',
        'ersetzt_buchung_id', 'begruendung', 'erfasst_von', 'erfasst_am',
    ];

    protected function casts(): array
    {
        return ['beginn' => 'datetime', 'ende' => 'datetime', 'erfasst_am' => 'datetime', 'gueltig' => 'boolean'];
    }

    /** @return BelongsTo<Arbeitsgang, $this> */
    public function arbeitsgang(): BelongsTo
    {
        return $this->belongsTo(Arbeitsgang::class);
    }

    /** @return BelongsTo<Benutzer, $this> */
    public function benutzer(): BelongsTo
    {
        return $this->belongsTo(Benutzer::class);
    }

    /** @return BelongsTo<Benutzer, $this> */
    public function erfasstVon(): BelongsTo
    {
        return $this->belongsTo(Benutzer::class, 'erfasst_von');
    }
}
