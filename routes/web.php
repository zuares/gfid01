<?php

use App\Http\Controllers\Accounting\JournalController;
use App\Http\Controllers\Inventory\MutationController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Production\CuttingInternalController;
use App\Http\Controllers\Production\CuttingReceiptController;

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

use App\Http\Controllers\Production\ExternalTransferController;
use App\Http\Controllers\Production\KittingController;

Route::prefix('production')->name('production.')->group(function () {
    // Tahap 1: Kirim ke tukang (create + store)
    Route::get('/external/send', [ExternalTransferController::class, 'create'])->name('external.send.create');
    Route::post('/external/send', [ExternalTransferController::class, 'store'])->name('external.send.store');

    // Tahap 2: Terima hasil cutting (draft + post)
    Route::get('/cutting/receive/create', [CuttingReceiptController::class, 'create'])->name('cutting.receive.create');
    Route::post('/cutting/receive', [CuttingReceiptController::class, 'storeDraft'])->name('cutting.receive.storeDraft');
    Route::post('/cutting/receive/{id}/post', [CuttingReceiptController::class, 'post'])->name('cutting.receive.post');
});

use App\Http\Controllers\Purchasing\PurchaseController;

Route::prefix('production')->name('production.')->group(function () {
    // Tahap 3: Cutting Internal (kg/m → pcs)
    Route::get('/cutting-internal/create', [CuttingInternalController::class, 'create'])->name('cutting.internal.create');
    Route::post('/cutting-internal', [CuttingInternalController::class, 'store'])->name('cutting.internal.store');
});

use App\Http\Controllers\Purchasing\PurchasePaymentController;

Route::prefix('production')->name('production.')->group(function () {
    Route::get('/kitting/create', [KittingController::class, 'create'])->name('kitting.create');
    Route::post('/kitting', [KittingController::class, 'store'])->name('kitting.store');
});
