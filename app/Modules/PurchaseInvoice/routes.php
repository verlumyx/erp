<?php

declare(strict_types=1);

use App\Modules\PurchaseInvoice\Controllers\PurchaseInvoiceGetController;
use App\Modules\PurchaseInvoice\Controllers\PurchaseInvoicePostController;
use App\Modules\PurchaseInvoice\Controllers\PurchaseInvoicePutController;
use App\Modules\PurchaseInvoice\Controllers\PurchaseInvoiceUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('purchase-invoices')->group(function () use ($uuid) {
            Route::get('/', [PurchaseInvoiceGetController::class, 'index'])->name('purchase-invoices.index');
            Route::get('/create', [PurchaseInvoiceGetController::class, 'create'])->name('purchase-invoices.create');
            Route::post('/', PurchaseInvoicePostController::class)->name('purchase-invoices.store');
            Route::get('/{id}', [PurchaseInvoiceGetController::class, 'show'])->where('id', $uuid)->name('purchase-invoices.show');
            Route::get('/{id}/edit', [PurchaseInvoiceGetController::class, 'edit'])->where('id', $uuid)->name('purchase-invoices.edit');
            Route::put('/{id}', PurchaseInvoicePutController::class)->where('id', $uuid)->name('purchase-invoices.update');
            Route::put('/{id}/status', PurchaseInvoiceUpdateStatusController::class)->where('id', $uuid)->name('purchase-invoices.update-status');
        });
    });
