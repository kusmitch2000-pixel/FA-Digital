<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Orders;
use App\Livewire\CreateOrder;
use App\Livewire\OrderDetail;
use App\Livewire\Terminal;
use App\Livewire\Catalog;
use App\Livewire\Workstations;
use App\Livewire\Users;
use App\Http\Controllers\ProductionController;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [ProductionController::class, 'dashboard'])->name('dashboard');
    Route::livewire('auftraege', Orders::class)->name('orders.index');
    Route::livewire('auftraege/neu', CreateOrder::class)->name('orders.create');
    Route::livewire('auftraege/{order}', OrderDetail::class)->name('orders.show');
    Route::livewire('werker', Terminal::class)->name('terminal');
    Route::livewire('artikel', Catalog::class)->name('articles.index');
    Route::livewire('arbeitsplaetze', Workstations::class)->name('workstations.index');
    Route::livewire('benutzer', Users::class)->name('users.index');
    Route::get('nachkalkulation', [ProductionController::class, 'costing'])->name('costing');
    Route::get('auswertung', [ProductionController::class, 'reports'])->name('reports');
});

require __DIR__.'/settings.php';
