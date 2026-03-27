<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::resource('parties', PartyController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
Route::get('parties/{party}/ledger', [PartyController::class, 'ledgerStatement'])->name('parties.ledger');

Route::resource('accounts', AccountController::class)->only(['index', 'create', 'store', 'show']);
Route::get('accounts/{account}/ledger', [AccountController::class, 'ledgerStatement'])->name('accounts.ledger');

Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
