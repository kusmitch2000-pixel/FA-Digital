<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Werkszertifikat nach DIN EN 10204, z. B. 2.2
 *
 * @property int $id
 * @property string $kuerzel
 * @property string $bezeichnung
 * @property string|null $beschreibung
 */
class Zertifikatsart extends Model
{
    protected $table = 'zertifikatsart';

    public $timestamps = false;

    protected $fillable = ['kuerzel', 'bezeichnung', 'beschreibung'];
}
