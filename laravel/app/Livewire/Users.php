<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class Users extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'werker';

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
    }

    public function create(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
            'role' => ['required', 'in:admin,werker'],
        ]);
        User::create([...$data, 'email_verified_at' => now()]);
        $this->reset('name', 'email', 'password');
        $this->role = 'werker';
    }

    public function render()
    {
        return view('livewire.users', ['users' => User::orderBy('name')->get()])
            ->layout('layouts::production', ['title' => 'Benutzer']);
    }
}
