<?php

declare(strict_types=1);

use App\Modules\MeasurementUnit\Controllers\MeasurementUnitGetController;
use App\Modules\MeasurementUnit\Controllers\MeasurementUnitPostController;
use App\Modules\MeasurementUnit\Controllers\MeasurementUnitPutController;
use App\Modules\MeasurementUnit\Controllers\MeasurementUnitUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('measurement-units')->group(function () use ($uuid) {
            Route::get('/', [MeasurementUnitGetController::class, 'index'])->name('measurement-units.index');
            Route::get('/create', [MeasurementUnitGetController::class, 'create'])->name('measurement-units.create');
            Route::post('/', MeasurementUnitPostController::class)->name('measurement-units.store');
            Route::get('/{id}', [MeasurementUnitGetController::class, 'show'])->where('id', $uuid)->name('measurement-units.show');
            Route::get('/{id}/edit', [MeasurementUnitGetController::class, 'edit'])->where('id', $uuid)->name('measurement-units.edit');
            Route::put('/{id}', MeasurementUnitPutController::class)->where('id', $uuid)->name('measurement-units.update');
            Route::put('/{id}/status', MeasurementUnitUpdateStatusController::class)->where('id', $uuid)->name('measurement-units.update-status');
        });
    });
