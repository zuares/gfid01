<?php

use App\Http\Controllers\Accounting\JournalController;
use App\Http\Controllers\Inventory\MutationController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Purchasing\PurchaseController;

Route::get('/dashboard', function () {
    return view('welcome');
})->name('dashboard');

use Illuminate\Support\Facades\Route;

Route::prefix('accounting')->name('accounting.')->group(function () {
    Route::get('/journals', [JournalController::class, 'index'])->name('journals.index');
    Route::get('/journals/{id}', [JournalController::class, 'show'])->name('journals.show');

    // Buku Besar (ledger)
    Route::get('/ledger', [JournalController::class, 'ledger'])->name('ledger');
});

Route::prefix('purchasing')->name('purchasing.')->group(function () {
    Route::get('/invoices', [PurchaseController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [PurchaseController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [PurchaseController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [PurchaseController::class, 'show'])->name('invoices.show');

    // AJAX helpers
    Route::get('/ajax/last-price', [PurchaseController::class, 'lastPrice'])->name('ajax.last_price');
    Route::get('/ajax/history', [PurchaseController::class, 'history'])->name('ajax.history');
});

Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::get('/mutations', [MutationController::class, 'index'])->name('mutations.index');
    Route::get('/mutations/{mutation}', [MutationController::class, 'show'])->name('mutations.show');
    Route::get('/stocks/breakdown/{itemCode}', [StockController::class, 'warehousesBreakdown'])
        ->name('stocks.breakdown');

});
