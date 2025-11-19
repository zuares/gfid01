<?php

use App\Http\Controllers\Inventory\ExternalTransferController;
use App\Http\Controllers\Inventory\MutationController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Inventory\StockReportController;

Route::middleware(['auth', 'role:admin'])->group(function () {

    // ========================
    // INVENTORY MODULE
    // ========================
    Route::prefix('inventory')->name('inventory.')->group(function () {

        // ------------------------
        // External Transfers
        // ------------------------
        Route::prefix('external-transfers')
            ->name('external_transfers.')
            ->group(function () {
                Route::get('/', [ExternalTransferController::class, 'index'])->name('index');
                Route::get('/create', [ExternalTransferController::class, 'create'])->name('create');
                Route::post('/', [ExternalTransferController::class, 'store'])->name('store');

                // Show detail External Transfer
                Route::get('/{transfer}', [ExternalTransferController::class, 'show'])
                    ->name('show');
                Route::get('/{transfer}/edit', [ExternalTransferController::class, 'edit'])->name('edit');
                Route::put('/{transfer}', [ExternalTransferController::class, 'update'])->name('update');
            });

        // ------------------------
        // Stocks
        // ------------------------
        Route::get('/stocks', [StockController::class, 'index'])
            ->name('stocks.index');

        Route::get('/stocks/breakdown/{itemCode}', [StockController::class, 'warehousesBreakdown'])
            ->name('stocks.breakdown');

        // ------------------------
        // Mutations
        // ------------------------
        Route::get('/mutations', [MutationController::class, 'index'])
            ->name('mutations.index');

        Route::get('/mutations/{mutation}', [MutationController::class, 'show'])
            ->name('mutations.show');

    });

});

Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('stock-position', [StockReportController::class, 'index'])
        ->name('stock_position.index');
});
