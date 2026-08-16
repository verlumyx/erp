<?php

declare(strict_types=1);

use App\Modules\Supplier\Controllers\SupplierGetController;
use App\Modules\Supplier\Controllers\SupplierPostController;
use App\Modules\Supplier\Controllers\SupplierPutController;
use App\Modules\Supplier\Controllers\SupplierUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('suppliers')->group(function () use ($uuid) {
            Route::get('/', [SupplierGetController::class, 'index'])->name('suppliers.index');
            Route::get('/create', [SupplierGetController::class, 'create'])->name('suppliers.create');
            Route::post('/', SupplierPostController::class)->name('suppliers.store');
            Route::get('/{id}', [SupplierGetController::class, 'show'])->where('id', $uuid)->name('suppliers.show');
            Route::get('/{id}/edit', [SupplierGetController::class, 'edit'])->where('id', $uuid)->name('suppliers.edit');
            Route::put('/{id}', SupplierPutController::class)->where('id', $uuid)->name('suppliers.update');
            Route::put('/{id}/status', SupplierUpdateStatusController::class)->where('id', $uuid)->name('suppliers.update-status');
        });
    });
