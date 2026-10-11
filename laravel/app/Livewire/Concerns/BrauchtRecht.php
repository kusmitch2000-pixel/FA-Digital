<?php

namespace App\Livewire\Concerns;

/**
 * Prüft bei jeder Anfrage einer Livewire-Seite das Recht aus config/fa.php.
 * Die Route prüft nur den ersten Aufruf. Klicks auf der Seite laufen über /livewire/update,
 * deshalb prüft Livewire hier bei jedem Klick noch einmal.
 */
trait BrauchtRecht
{
    public function bootBrauchtRecht(): void
    {
        $this->pruefeRecht($this->recht);
    }

    /** Zusätzliche Rechte für einzelne Aktionen, z. B. pruefeRecht('buchung.korrigieren') */
    protected function pruefeRecht(string $recht): void
    {
        abort_unless(auth()->user()?->darf($recht), 403, 'Diese Aktion ist für Ihre Rolle nicht freigegeben.');
    }

    protected function darf(string $recht): bool
    {
        return (bool) auth()->user()?->darf($recht);
    }
}
