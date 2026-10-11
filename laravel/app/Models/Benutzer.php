<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Angemeldete Person, Tabelle benutzer aus datenbank/01_schema.sql.
 * Das Passwort steht als Hash in passwort_hash. Die Rolle bestimmt die sichtbaren Seiten.
 *
 * @property int $id
 * @property string $personalnummer
 * @property string $vorname
 * @property string $nachname
 * @property string $email
 * @property string $passwort_hash
 * @property 'arbeitsvorbereitung'|'werker'|'meister'|'controlling'|'admin' $rolle
 * @property bool $aktiv
 * @property CarbonImmutable $angelegt_am
 * @property-read string $name
 * @property-read string $rollenbezeichnung
 */
class Benutzer extends Authenticatable
{
    protected $table = 'benutzer';

    public $timestamps = false;

    protected $authPasswordName = 'passwort_hash';

    protected $fillable = ['personalnummer', 'vorname', 'nachname', 'email', 'passwort_hash', 'rolle', 'aktiv'];

    protected $hidden = ['passwort_hash'];

    protected function casts(): array
    {
        return [
            'aktiv' => 'boolean',
            'angelegt_am' => 'datetime',
            'passwort_hash' => 'hashed',
        ];
    }

    public function getNameAttribute(): string
    {
        return $this->vorname.' '.$this->nachname;
    }

    public function getRollenbezeichnungAttribute(): string
    {
        return config('fa.rollen.'.$this->rolle, $this->rolle);
    }

    /** Recht aus config/fa.php prüfen, z. B. darf('buchung.korrigieren') */
    public function darf(string $recht): bool
    {
        // Die Schlüssel enthalten Punkte (z. B. auftrag.anlegen), deshalb nicht config('fa.rechte.'.$recht)
        return $this->aktiv && in_array($this->rolle, config('fa.rechte')[$recht] ?? [], true);
    }

    public function startseite(): string
    {
        return route(config('fa.startseite.'.$this->rolle, 'auftraege'));
    }
}
