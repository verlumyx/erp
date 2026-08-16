<?php

declare(strict_types=1);

use App\Modules\SupplierType\Controllers\SupplierTypeGetController;
use App\Modules\SupplierType\Controllers\SupplierTypePostController;
use App\Modules\SupplierType\Controllers\SupplierTypePutController;
use App\Modules\SupplierType\Controllers\SupplierTypeUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('supplier-types')->group(function () use ($uuid) {
            Route::get('/', [SupplierTypeGetController::class, 'index'])->name('supplier-types.index');
            Route::get('/create', [SupplierTypeGetController::class, 'create'])->name('supplier-types.create');
            Route::post('/', SupplierTypePostController::class)->name('supplier-types.store');
            Route::get('/{id}', [SupplierTypeGetController::class, 'show'])->where('id', $uuid)->name('supplier-types.show');
            Route::get('/{id}/edit', [SupplierTypeGetController::class, 'edit'])->where('id', $uuid)->name('supplier-types.edit');
            Route::put('/{id}', SupplierTypePutController::class)->where('id', $uuid)->name('supplier-types.update');
            Route::put('/{id}/status', SupplierTypeUpdateStatusController::class)->where('id', $uuid)->name('supplier-types.update-status');
        });
    });
