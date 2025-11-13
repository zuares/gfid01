<?php

use App\Http\Controllers\Accounting\JournalController;
use App\Http\Controllers\Inventory\MutationController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Master\WarehouseController;
use App\Http\Controllers\Production\ExternalTransferController;
use App\Http\Controllers\Purchasing\PurchaseController;
use App\Http\Controllers\Purchasing\PurchasePaymentController;

Route::get('/dashboard', function () {
    return view('welcome');
})->name('dashboard');

Route::prefix('accounting')->name('accounting.')->group(function () {
    Route::get('/journals', [JournalController::class, 'index'])->name('journals.index');
    Route::get('/journals/create', [JournalController::class, 'create'])->name('journals.create');
    Route::post('/journals', [JournalController::class, 'store'])->name('journals.store');
    Route::get('/journals/{id}', [JournalController::class, 'show'])->name('journals.show');

    // Buku Besar (ledger)
    Route::get('/ledger', [JournalController::class, 'ledger'])->name('ledger');
});
// routes/web.php

Route::prefix('purchasing/invoices')->name('purchasing.invoices.')->group(function () {
    Route::get('/', [PurchaseController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseController::class, 'create'])->name('create');
    Route::post('/', [PurchaseController::class, 'store'])->name('store');
    Route::get('/{invoice}', [PurchaseController::class, 'show'])->name('show');

    // ✏️ Edit & update detail baris invoice
    Route::get('/{invoice}/edit-lines', [PurchaseController::class, 'editLines'])
        ->name('lines.edit');

    Route::put('/{invoice}/lines', [PurchaseController::class, 'updateLines'])
        ->name('lines.update');

    // AJAX
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

// routes/web.php
// routes/web.php
// routes/web.php
Route::prefix('purchasing')->name('purchasing.')->group(function () {
    // ... route invoice index/show/create/store dsb
    Route::post('/invoices/{invoice}/payments', [PurchasePaymentController::class, 'store'])
        ->name('invoices.payments.store');
    Route::delete('/invoices/{invoice}/payments/{payment}', [PurchasePaymentController::class, 'destroy'])
        ->name('invoices.payments.destroy');
    Route::post('/invoices/{invoice}/post', [PurchaseController::class, 'post'])
        ->name('invoices.post');
});

// routes/web.php (potong yang penting)

Route::prefix('production/external')->name('production.external.')->group(function () {
    Route::get('/', [ExternalTransferController::class, 'index'])->name('index');
    Route::get('/create', [ExternalTransferController::class, 'create'])->name('create');
    Route::post('/', [ExternalTransferController::class, 'store'])->name('store');

    Route::post('/{id}/send', [ExternalTransferController::class, 'send'])->name('send');

    Route::get('/{id}/receive', [ExternalTransferController::class, 'receiveForm'])->name('receive.form');
    Route::post('/{id}/receive', [ExternalTransferController::class, 'receiveStore'])->name('receive.store');

    Route::post('/{id}/post', [ExternalTransferController::class, 'post'])->name('post');
});

Route::prefix('master/warehouses')
    ->name('master.warehouses.')
    ->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])->name('index');
        Route::get('/create', [WarehouseController::class, 'create'])->name('create');
        Route::post('/', [WarehouseController::class, 'store'])->name('store');
        Route::get('/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('edit');
        Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
        Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy');
    });
