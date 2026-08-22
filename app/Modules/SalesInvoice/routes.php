<?php

declare(strict_types=1);

use App\Modules\SalesInvoice\Controllers\SalesInvoiceGetController;
use App\Modules\SalesInvoice\Controllers\SalesInvoicePostController;
use App\Modules\SalesInvoice\Controllers\SalesInvoicePutController;
use App\Modules\SalesInvoice\Controllers\SalesInvoiceUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('sales-invoices')->group(function () use ($uuid) {
            Route::get('/', [SalesInvoiceGetController::class, 'index'])->name('sales-invoices.index');
            Route::get('/create', [SalesInvoiceGetController::class, 'create'])->name('sales-invoices.create');
            Route::post('/', SalesInvoicePostController::class)->name('sales-invoices.store');
            Route::get('/{id}', [SalesInvoiceGetController::class, 'show'])->where('id', $uuid)->name('sales-invoices.show');
            Route::get('/{id}/edit', [SalesInvoiceGetController::class, 'edit'])->where('id', $uuid)->name('sales-invoices.edit');
            Route::put('/{id}', SalesInvoicePutController::class)->where('id', $uuid)->name('sales-invoices.update');
            Route::put('/{id}/status', SalesInvoiceUpdateStatusController::class)->where('id', $uuid)->name('sales-invoices.update-status');
        });
    });
