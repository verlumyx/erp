<?php

declare(strict_types=1);

use App\Modules\SupplierPayment\Controllers\SupplierPaymentGetController;
use App\Modules\SupplierPayment\Controllers\SupplierPaymentPostController;
use App\Modules\SupplierPayment\Controllers\SupplierPaymentPutController;
use App\Modules\SupplierPayment\Controllers\SupplierPaymentUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('supplier-payments')->group(function () use ($uuid) {
            Route::get('/', [SupplierPaymentGetController::class, 'index'])->name('supplier-payments.index');
            Route::get('/create', [SupplierPaymentGetController::class, 'create'])->name('supplier-payments.create');
            Route::post('/', SupplierPaymentPostController::class)->name('supplier-payments.store');
            Route::get('/{id}', [SupplierPaymentGetController::class, 'show'])->where('id', $uuid)->name('supplier-payments.show');
            Route::get('/{id}/edit', [SupplierPaymentGetController::class, 'edit'])->where('id', $uuid)->name('supplier-payments.edit');
            Route::put('/{id}', SupplierPaymentPutController::class)->where('id', $uuid)->name('supplier-payments.update');
            Route::put('/{id}/status', SupplierPaymentUpdateStatusController::class)->where('id', $uuid)->name('supplier-payments.update-status');
        });
    });
