<?php

use App\Http\Controllers\CatalogItemController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PbAllowanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('invoices.index'));

Route::resource('invoices', InvoiceController::class);
Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.status.update');

Route::resource('catalog-items', CatalogItemController::class)
    ->except(['show']);

Route::get('pb-allowances', [PbAllowanceController::class, 'index'])
    ->name('pb-allowances.index');
