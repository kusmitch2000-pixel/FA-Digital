<?php

namespace App\Livewire;

use App\Livewire\Concerns\BrauchtRecht;
use App\Models\Benutzer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Livewire\Component;

/** Benutzerverwaltung (Anwendungsfall 12, Admin): Konten anlegen, sperren und Passwörter neu setzen */
class Benutzerverwaltung extends Component
{
    use BrauchtRecht;

    protected string $recht = 'benutzer';

    public string $personalnummer = '';

    public string $vorname = '';

    public string $nachname = '';

    public string $email = '';

    public string $rolle = 'werker';

    public string $passwort = '';

    /** Neues Passwort für ein bestehendes Konto */
    public ?int $passwortFuer = null;

    public string $neuesPasswort = '';

    public function anlegen(): void
    {
        $this->resetErrorBag();
        $daten = $this->validate([
            'personalnummer' => ['required', 'string', 'max:10', 'unique:benutzer,personalnummer'],
            'vorname' => ['required', 'string', 'max:50'],
            'nachname' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', 'unique:benutzer,email'],
            'rolle' => ['required', Rule::in(array_keys(config('fa.rollen')))],
            'passwort' => ['required', 'string', 'min:10'],
        ]);

        // Der Cast "hashed" im Model speichert nur den Hash, nie das Passwort selbst
        Benutzer::create([...Arr::except($daten, 'passwort'), 'passwort_hash' => $daten['passwort'], 'aktiv' => true]);

        $this->reset('personalnummer', 'vorname', 'nachname', 'email', 'passwort');
        $this->rolle = 'werker';
        session()->flash('status', "{$daten['vorname']} {$daten['nachname']} kann sich jetzt anmelden.");
    }

    public function aktivUmschalten(int $id): void
    {
        $this->resetErrorBag();
        $benutzer = Benutzer::findOrFail($id);

        if ($benutzer->is(auth()->user())) {
            $this->addError('aktion', 'Das eigene Konto lässt sich nicht sperren.');

            return;
        }

        $benutzer->update(['aktiv' => ! $benutzer->aktiv]);
    }

    public function passwortSetzen(): void
    {
        $this->resetErrorBag();
        $this->validate(['passwortFuer' => ['required', 'exists:benutzer,id'], 'neuesPasswort' => ['required', 'string', 'min:10']]);

        $benutzer = Benutzer::findOrFail($this->passwortFuer);
        $benutzer->update(['passwort_hash' => $this->neuesPasswort]);

        $this->reset('passwortFuer', 'neuesPasswort');
        session()->flash('status', "Neues Passwort für {$benutzer->name} gespeichert.");
    }

    public function render(): View
    {
        return view('livewire.benutzerverwaltung', [
            'benutzer' => Benutzer::orderByDesc('aktiv')->orderBy('nachname')->get(),
            'rollen' => config('fa.rollen'),
        ])->layout('layouts::production', ['title' => 'Benutzer']);
    }
}
