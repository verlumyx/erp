<?php

declare(strict_types=1);

use App\Modules\Vehicle\Controllers\VehicleGetController;
use App\Modules\Vehicle\Controllers\VehiclePostController;
use App\Modules\Vehicle\Controllers\VehiclePutController;
use App\Modules\Vehicle\Controllers\VehicleUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('vehicles')->group(function () use ($uuid) {
            Route::get('/', [VehicleGetController::class, 'index'])->name('vehicles.index');
            Route::get('/create', [VehicleGetController::class, 'create'])->name('vehicles.create');
            Route::get('/lookup', [VehicleGetController::class, 'lookup'])->name('vehicles.lookup');
            Route::post('/', VehiclePostController::class)->name('vehicles.store');
            Route::get('/{id}', [VehicleGetController::class, 'show'])->where('id', $uuid)->name('vehicles.show');
            Route::get('/{id}/edit', [VehicleGetController::class, 'edit'])->where('id', $uuid)->name('vehicles.edit');
            Route::put('/{id}', VehiclePutController::class)->where('id', $uuid)->name('vehicles.update');
            Route::put('/{id}/status', VehicleUpdateStatusController::class)->where('id', $uuid)->name('vehicles.update-status');
        });
    });
