<?php

use App\Http\Controllers\CsvExportController;
use App\Http\Controllers\WerkszeugnisController;
use App\Livewire\Arbeitsplaene;
use App\Livewire\Arbeitsplaetze;
use App\Livewire\Auftraege;
use App\Livewire\AuftragAnlegen;
use App\Livewire\AuftragDetail;
use App\Livewire\Auswertung;
use App\Livewire\Benutzerverwaltung;
use App\Livewire\Hallenansicht;
use App\Livewire\Nachkalkulation;
use App\Livewire\Terminal;
use App\Livewire\Werkszertifikate;
use Illuminate\Support\Facades\Route;

// Anmelden und Abmelden stellt Fortify bereit (/login, /logout).
// Welche Rolle welche Seite sieht, steht in config/fa.php unter „rechte“.

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect(auth()->user()->startseite()))->name('home');

    // Werkstatt
    Route::livewire('werker', Terminal::class)->middleware('recht:terminal')->name('terminal');
    Route::livewire('halle', Hallenansicht::class)->middleware('recht:halle')->name('halle');

    // Aufträge
    Route::livewire('auftraege', Auftraege::class)->middleware('recht:auftraege')->name('auftraege');
    Route::livewire('auftraege/neu', AuftragAnlegen::class)->middleware('recht:auftrag.anlegen')->name('auftrag.anlegen');
    Route::livewire('auftraege/{auftrag}', AuftragDetail::class)->middleware('recht:auftraege')->name('auftrag');
    Route::livewire('zertifikate', Werkszertifikate::class)->middleware('recht:zertifikate')->name('zertifikate');
    Route::get('zertifikate/{zertifikat}', WerkszeugnisController::class)->middleware('recht:zertifikate')->name('zertifikat.druck');

    // Controlling
    Route::livewire('nachkalkulation', Nachkalkulation::class)->middleware('recht:nachkalkulation')->name('nachkalkulation');
    Route::livewire('auswertung', Auswertung::class)->middleware('recht:auswertung')->name('auswertung');
    Route::get('auswertung/{bereich}.csv', CsvExportController::class)->middleware('recht:auswertung')->name('auswertung.export');

    // Stammdaten und Verwaltung
    Route::livewire('artikel', Arbeitsplaene::class)->middleware('recht:arbeitsplaene')->name('arbeitsplaene');
    Route::livewire('arbeitsplaetze', Arbeitsplaetze::class)->middleware('recht:arbeitsplaetze')->name('arbeitsplaetze');
    Route::livewire('benutzer', Benutzerverwaltung::class)->middleware('recht:benutzer')->name('benutzer');
});
