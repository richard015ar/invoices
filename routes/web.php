<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CatalogItemController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\IssuerProfileController;
use App\Http\Controllers\PbAllowanceController;
use App\Http\Controllers\TaxSummaryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('invoices.index')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store')->middleware('throttle:10,1');

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->name('register.store')->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('invoices/{invoice}/attachments/{attachment}', [InvoiceController::class, 'downloadAttachment'])->name('invoices.attachments.download');
    Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.status.update');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');

    Route::resource('catalog-items', CatalogItemController::class)
        ->except(['show']);

    Route::resource('clients', ClientController::class)
        ->except(['show']);

    Route::get('issuer-profile', [IssuerProfileController::class, 'edit'])->name('issuer-profile.edit');
    Route::put('issuer-profile', [IssuerProfileController::class, 'update'])->name('issuer-profile.update');

    Route::get('pb-allowances', [PbAllowanceController::class, 'index'])
        ->name('pb-allowances.index');

    Route::get('tax-summary', [TaxSummaryController::class, 'index'])->name('tax-summary.index');
    Route::post('tax-summary/settings', [TaxSummaryController::class, 'updateSettings'])->name('tax-summary.settings');
});
