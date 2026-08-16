<?php

declare(strict_types=1);

use App\Modules\WarehouseLocation\Controllers\WarehouseLocationGetController;
use App\Modules\WarehouseLocation\Controllers\WarehouseLocationPostController;
use App\Modules\WarehouseLocation\Controllers\WarehouseLocationPutController;
use App\Modules\WarehouseLocation\Controllers\WarehouseLocationUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('warehouse-locations')->group(function () use ($uuid) {
            Route::get('/', [WarehouseLocationGetController::class, 'index'])->name('warehouse-locations.index');
            Route::get('/create', [WarehouseLocationGetController::class, 'create'])->name('warehouse-locations.create');
            Route::post('/', WarehouseLocationPostController::class)->name('warehouse-locations.store');
            Route::get('/{id}', [WarehouseLocationGetController::class, 'show'])->where('id', $uuid)->name('warehouse-locations.show');
            Route::get('/{id}/edit', [WarehouseLocationGetController::class, 'edit'])->where('id', $uuid)->name('warehouse-locations.edit');
            Route::put('/{id}', WarehouseLocationPutController::class)->where('id', $uuid)->name('warehouse-locations.update');
            Route::put('/{id}/status', WarehouseLocationUpdateStatusController::class)->where('id', $uuid)->name('warehouse-locations.update-status');
        });
    });
