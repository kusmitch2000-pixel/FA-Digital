<?php

namespace App\Actions\Fertigung;

use Illuminate\Validation\ValidationException;

/** Verstoß gegen eine Fachregel. Livewire zeigt die Meldung wie einen Eingabefehler an. */
class Fachfehler
{
    public static function melden(string $meldung, string $feld = 'aktion'): never
    {
        throw ValidationException::withMessages([$feld => $meldung]);
    }
}
