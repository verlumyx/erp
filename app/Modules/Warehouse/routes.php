<?php

declare(strict_types=1);

use App\Modules\Warehouse\Controllers\WarehouseGetController;
use App\Modules\Warehouse\Controllers\WarehousePostController;
use App\Modules\Warehouse\Controllers\WarehousePutController;
use App\Modules\Warehouse\Controllers\WarehouseUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('warehouses')->group(function () use ($uuid) {
            Route::get('/', [WarehouseGetController::class, 'index'])->name('warehouses.index');
            Route::get('/create', [WarehouseGetController::class, 'create'])->name('warehouses.create');
            Route::post('/', WarehousePostController::class)->name('warehouses.store');
            Route::get('/{id}', [WarehouseGetController::class, 'show'])->where('id', $uuid)->name('warehouses.show');
            Route::get('/{id}/edit', [WarehouseGetController::class, 'edit'])->where('id', $uuid)->name('warehouses.edit');
            Route::put('/{id}', WarehousePutController::class)->where('id', $uuid)->name('warehouses.update');
            Route::put('/{id}/status', WarehouseUpdateStatusController::class)->where('id', $uuid)->name('warehouses.update-status');
        });
    });
