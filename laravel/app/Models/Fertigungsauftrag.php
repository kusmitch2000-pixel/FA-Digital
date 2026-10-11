<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $auftragsnummer
 * @property int $artikel_id
 * @property int $menge
 * @property string|null $kunde
 * @property CarbonImmutable $liefertermin
 * @property 'angelegt'|'freigegeben'|'in_arbeit'|'fertig'|'nachkalkuliert'|'storniert' $status
 * @property string|null $bemerkung
 * @property int $angelegt_von
 * @property CarbonImmutable $angelegt_am
 * @property int|null $freigegeben_von
 * @property CarbonImmutable|null $freigegeben_am
 * @property int|null $abgeschlossen_von
 * @property CarbonImmutable|null $abgeschlossen_am
 * @property CarbonImmutable|null $nachkalkuliert_am
 * @property-read Artikel $artikel
 * @property-read Collection<int, Arbeitsgang> $arbeitsgaenge
 * @property-read Collection<int, AuftragZertifikat> $zertifikate
 * @property-read Benutzer $angelegtVon
 */
class Fertigungsauftrag extends Model
{
    protected $table = 'fertigungsauftrag';

    public $timestamps = false;

    protected $fillable = [
        'auftragsnummer', 'artikel_id', 'menge', 'kunde', 'liefertermin', 'status', 'bemerkung',
        'angelegt_von', 'freigegeben_von', 'freigegeben_am', 'abgeschlossen_von', 'abgeschlossen_am', 'nachkalkuliert_am',
    ];

    protected function casts(): array
    {
        return [
            'liefertermin' => 'date',
            'angelegt_am' => 'datetime',
            'freigegeben_am' => 'datetime',
            'abgeschlossen_am' => 'datetime',
            'nachkalkuliert_am' => 'datetime',
        ];
    }

    /** In Adressen steht die Auftragsnummer, z. B. /auftraege/FA-1002 */
    public function getRouteKeyName(): string
    {
        return 'auftragsnummer';
    }

    /** @return BelongsTo<Artikel, $this> */
    public function artikel(): BelongsTo
    {
        return $this->belongsTo(Artikel::class);
    }

    /** @return HasMany<Arbeitsgang, $this> */
    public function arbeitsgaenge(): HasMany
    {
        return $this->hasMany(Arbeitsgang::class)->orderBy('ag_nummer');
    }

    /** @return HasMany<AuftragZertifikat, $this> */
    public function zertifikate(): HasMany
    {
        return $this->hasMany(AuftragZertifikat::class);
    }

    /** @return BelongsTo<Benutzer, $this> */
    public function angelegtVon(): BelongsTo
    {
        return $this->belongsTo(Benutzer::class, 'angelegt_von');
    }
}
