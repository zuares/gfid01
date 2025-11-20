<?php

use App\Http\Controllers\Production\FinishingController;
use App\Http\Controllers\Production\HppController;
use App\Http\Controllers\Production\PackingJobController;
use App\Http\Controllers\Production\SewingPickController;
use App\Http\Controllers\Production\SewingReportController;
use App\Http\Controllers\Production\SewingReturnController;
use App\Http\Controllers\Production\VendorCuttingController;
use App\Http\Controllers\Production\WipCuttingQcController;

Route::middleware(['auth', 'role:cutting,admin'])->group(function () {

    Route::prefix('production')->name('production.')->group(function () {

        // ==========================
        // VENDOR CUTTING
        // ==========================
        Route::prefix('vendor-cutting')->name('vendor_cutting.')
            ->group(function () {

                // ... route yang sudah ada (index, receive, showBatch) ...
                Route::get('/', [VendorCuttingController::class, 'index'])->name('index');

                Route::get('/receive/{externalTransfer}', [VendorCuttingController::class, 'receiveForm'])
                    ->name('receive.form');

                Route::post('/receive/{externalTransfer}', [VendorCuttingController::class, 'receiveStore'])
                    ->name('receive.store');

                Route::get('/batches/{batch}', [VendorCuttingController::class, 'showBatch'])
                    ->name('batches.show');

                // STEP 2: input hasil cutting per iket
                Route::get('/batches/{batch}/results', [VendorCuttingController::class, 'editResults'])
                    ->name('batches.results.edit');

                Route::post('/batches/{batch}/results', [VendorCuttingController::class, 'updateResults'])
                    ->name('batches.results.update');

                Route::post('/batches/{batch}/send-to-qc', [VendorCuttingController::class, 'sendToQc'])
                    ->name('batches.send_to_qc');
            });

        // ==========================
        // QC CUTTING (WIP)
        // ==========================
        Route::prefix('wip-cutting-qc')
            ->name('wip_cutting_qc.')
            ->group(function () {

                Route::get('/', [WipCuttingQcController::class, 'index'])
                    ->name('index');

                Route::get('/{batch}', [WipCuttingQcController::class, 'show'])
                    ->name('show'); // ROUTE SHOW

                Route::get('/{batch}/edit', [WipCuttingQcController::class, 'edit'])
                    ->name('edit');

                Route::post('/{batch}', [WipCuttingQcController::class, 'update'])
                    ->name('update');
            });

        // ==========================
        // AMBIL JAHIT (SEWING PICKS)
        // ==========================
        Route::prefix('sewing-picks')
            ->name('sewing_picks.')
            ->group(function () {

                // List dokumen ambil jahit
                Route::get('/', [SewingPickController::class, 'index'])
                    ->name('index');

                // Form buat dokumen ambil jahit (bisa dari WIP Cutting / WIP Sewing index)
                Route::get('/create', [SewingPickController::class, 'create'])
                    ->name('create');

                // Simpan dokumen ambil jahit + mutasi stok (InventoryService->transfer)
                Route::post('/', [SewingPickController::class, 'store'])
                    ->name('store');

                // Detail 1 dokumen ambil jahit
                Route::get('/{sewingPick}', [SewingPickController::class, 'show'])
                    ->name('show');

                // (opsional) hapus / batal
                // Route::delete('/{sewingPick}', [SewingPickController::class, 'destroy'])
                //     ->name('destroy');
            });

        // ==========================
        // SETOR JAHIT (SEWING RETURNS)
        // ==========================
        Route::prefix('sewing-returns')
            ->name('sewing_returns.')
            ->group(function () {

                // List dokumen setor jahit
                Route::get('/', [SewingReturnController::class, 'index'])
                    ->name('index');

                // Form input setor hasil jahit (OK & Reject)
                Route::get('/create', [SewingReturnController::class, 'create'])
                    ->name('create');

                // Simpan setor jahit + mutasi stok (OK & Reject)
                Route::post('/', [SewingReturnController::class, 'store'])
                    ->name('store');

                // Detail 1 dokumen setor jahit
                Route::get('/{sewingReturn}', [SewingReturnController::class, 'show'])
                    ->name('show');

                Route::post('{sewingReturn}/post', [SewingReturnController::class, 'post'])
                    ->name('post');
                // (opsional) batal / hapus
                // Route::delete('/{sewingReturn}', [SewingReturnController::class, 'destroy'])
                //     ->name('destroy');
            });

        // ==========================
        // REPORT SEWING
        // ==========================
        Route::get('sewing-report/sisa-operator', [SewingReportController::class, 'sisaPerOperator'])
            ->name('sewing_report.sisa_operator');

    });

});

Route::prefix('production/sewing-report')
    ->name('production.sewing_report.')
    ->group(function () {
        Route::get('/operators', [SewingReportController::class, 'operatorBalance'])
            ->name('operator_balance');

        Route::get('/operators/export', [SewingReportController::class, 'exportOperatorBalance'])
            ->name('operator_balance_export');

        Route::get('/operators/{operator}', [SewingReportController::class, 'operatorDetail'])
            ->name('operator_detail');

        Route::get('/operators/{operator}/export', [SewingReportController::class, 'exportOperatorDetail'])
            ->name('operator_detail_export');

    });

Route::middleware(['auth', 'role:admin,finishing'])->group(function () {
    Route::prefix('production')->name('production.')->group(function () {

        Route::prefix('packing-jobs')->name('packing_jobs.')->group(function () {
            Route::get('/', [PackingJobController::class, 'index'])->name('index');
            Route::get('/create', [PackingJobController::class, 'create'])->name('create');
            Route::post('/', [PackingJobController::class, 'store'])->name('store');
            Route::get('/{packingJob}', [PackingJobController::class, 'show'])->name('show');
            Route::post('/{packingJob}/post', [PackingJobController::class, 'post'])->name('post');
        });

    });
});

Route::middleware(['auth', 'role:owner,admin']) // plus middleware role kalau ada
    ->prefix('production')
    ->name('production.')
    ->group(function () {
        Route::get('finishing', [FinishingController::class, 'index'])->name('finishing.index');
        Route::get('finishing/create', [FinishingController::class, 'create'])->name('finishing.create');
        Route::post('finishing', [FinishingController::class, 'store'])->name('finishing.store');
        Route::get('finishing/{finishingJob}', [FinishingController::class, 'show'])->name('finishing.show');
    });

Route::prefix('production')
    ->name('production.')
    ->middleware(['auth'])
    ->group(function () {

        // ... route lain produksi

        // HPP INDEX
        Route::get('hpp', [HppController::class, 'index'])
            ->name('hpp.index');

        // DETAIL per ITEM
        Route::get('hpp/items/{item}', [HppController::class, 'showItem'])
            ->name('hpp.items.show');

        // DETAIL per LOT
        Route::get('hpp/lots/{lot}', [HppController::class, 'showLot'])
            ->name('hpp.lots.show');
    });
