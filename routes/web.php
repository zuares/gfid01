<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard.index');
})->name('dashboard');

// Pisah per modul
require __DIR__ . '/web/accounting.php';
require __DIR__ . '/web/purchasing.php';
require __DIR__ . '/web/inventory.php';
require __DIR__ . '/web/master.php';
require __DIR__ . '/web/production.php';
require __DIR__ . '/web/payroll.php';
require __DIR__ . '/web/ajax.php';
require __DIR__ . '/web/login.php';
require __DIR__ . '/web/auth.php';
