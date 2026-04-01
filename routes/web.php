<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeSalaryController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('parties', PartyController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::get('parties/{party}/ledger', [PartyController::class, 'ledgerStatement'])->name('parties.ledger');
    Route::patch('parties/{party}/opening-balance', [PartyController::class, 'updateOpeningBalance'])->name('parties.opening-balance.update');

    Route::resource('accounts', AccountController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('accounts/{account}/ledger', [AccountController::class, 'ledgerStatement'])->name('accounts.ledger');
    Route::patch('accounts/{account}/opening-balance', [AccountController::class, 'updateOpeningBalance'])->name('accounts.opening-balance.update');

    Route::resource('employees', EmployeeController::class)->only(['index', 'create', 'store', 'show']);

    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::resource('employee-salaries', EmployeeSalaryController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('employee-salaries/{employeeSalary}/print', [EmployeeSalaryController::class, 'print'])->name('employee-salaries.print');

    Route::get('reports/cashbook', [ReportController::class, 'cashbook'])->name('reports.cashbook');
    Route::get('reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
});
