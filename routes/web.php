<?php

use App\Http\Controllers\CatalogItemController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\IssuerProfileController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PbAllowanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('invoices.index'));

Route::resource('invoices', InvoiceController::class);
Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
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
