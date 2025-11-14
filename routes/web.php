<?php

use App\Http\Controllers\Accounting\JournalController;
use App\Http\Controllers\Ajax\ItemLookupController;
use App\Http\Controllers\Inventory\MutationController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Master\WarehouseController;
use App\Http\Controllers\Payroll\PayrollPerPieceController;
use App\Http\Controllers\Production\ExternalTransferController;
use App\Http\Controllers\Production\FinishingController;
use App\Http\Controllers\Production\SewingController;
use App\Http\Controllers\Production\VendorCuttingController;
use App\Http\Controllers\Purchasing\PurchaseController;
use App\Http\Controllers\Purchasing\PurchasePaymentController;

Route::get('/', function () {
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

// Route::prefix('production/cutting/external-inbound')
//     ->name('production.cutting.external-inbound.')
//     ->group(function () {
//         Route::get('/', [CuttingExternalInboundController::class, 'index'])
//             ->name('index');

//         Route::get('/{id}', [CuttingExternalInboundController::class, 'show'])
//             ->name('show');

//         Route::post('/{id}/confirm', [CuttingExternalInboundController::class, 'confirm'])
//             ->name('confirm');
//     });

Route::prefix('production/external')->name('external-transfers.')->group(function () {
    Route::get('/', [ExternalTransferController::class, 'index'])->name('index');
    Route::get('/create', [ExternalTransferController::class, 'create'])->name('create');
    Route::post('/', [ExternalTransferController::class, 'store'])->name('store');

    Route::get('/{externalTransfer}', [ExternalTransferController::class, 'show'])->name('show');
    Route::get('/{externalTransfer}/edit', [ExternalTransferController::class, 'edit'])->name('edit');
    Route::put('/{externalTransfer}', [ExternalTransferController::class, 'update'])->name('update');
    Route::delete('/{externalTransfer}', [ExternalTransferController::class, 'destroy'])->name('destroy');

    Route::post('/{id}/send', [ExternalTransferController::class, 'send'])->name('send');
    Route::post('/{externalTransfer}/receive', [ExternalTransferController::class, 'receive'])->name('receive');
    Route::post('/{externalTransfer}/done', [ExternalTransferController::class, 'done'])->name('done');
});

Route::prefix('production/vendor-cutting')->name('vendor-cutting.')->group(function () {
    Route::get('/', [VendorCuttingController::class, 'index'])->name('index');
    Route::get('/{externalTransfer}', [VendorCuttingController::class, 'create'])->name('create');
    Route::post('/{externalTransfer}', [VendorCuttingController::class, 'store'])->name('store');
});

Route::prefix('production/sewing')
    ->name('sewing.')
    ->group(function () {
        Route::get('/', [SewingController::class, 'index'])->name('index');

        // ➜ Tambahan baru:
        Route::get('{wipItem}/create', [SewingController::class, 'create'])->name('create');
        Route::post('{wipItem}', [SewingController::class, 'store'])->name('store');
    });

Route::prefix('production/finishing')
    ->name('finishing.')
    ->group(function () {
        Route::get('/', [FinishingController::class, 'index'])->name('index');
        Route::get('{wipItem}/create', [FinishingController::class, 'create'])->name('create');
        Route::post('{wipItem}', [FinishingController::class, 'store'])->name('store');
        Route::get('{wipItem}', [FinishingController::class, 'show'])->name('show');
    });

Route::get('/ajax/items/finished', [ItemLookupController::class, 'searchFinished'])
    ->name('ajax.items.finished');

Route::prefix('payroll/runs')
    ->name('payroll.runs.')
    ->group(function () {
        Route::get('/', [PayrollPerPieceController::class, 'index'])->name('index');
        Route::post('/', [PayrollPerPieceController::class, 'store'])->name('store');
        Route::get('{payrollRun}', [PayrollPerPieceController::class, 'show'])->name('show');

        Route::post('{payrollRun}/post', [PayrollPerPieceController::class, 'post'])->name('post');
    });
