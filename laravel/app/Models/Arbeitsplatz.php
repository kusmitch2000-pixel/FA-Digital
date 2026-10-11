<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nummer
 * @property string $bezeichnung
 * @property string $kostenstelle
 * @property string $stundensatz Euro pro Stunde
 * @property bool $aktiv
 */
class Arbeitsplatz extends Model
{
    protected $table = 'arbeitsplatz';

    public $timestamps = false;

    protected $fillable = ['nummer', 'bezeichnung', 'kostenstelle', 'stundensatz', 'aktiv'];

    protected function casts(): array
    {
        return ['stundensatz' => 'decimal:2', 'aktiv' => 'boolean'];
    }
}
