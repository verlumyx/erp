<?php

declare(strict_types=1);

use App\Modules\Dispatch\Controllers\DispatchDeliveryController;
use App\Modules\Dispatch\Controllers\DispatchGetController;
use App\Modules\Dispatch\Controllers\DispatchPostController;
use App\Modules\Dispatch\Controllers\DispatchPutController;
use App\Modules\Dispatch\Controllers\DispatchUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('dispatches')->group(function () use ($uuid) {
            Route::get('/', [DispatchGetController::class, 'index'])->name('dispatches.index');
            Route::get('/create', [DispatchGetController::class, 'create'])->name('dispatches.create');
            Route::get('/lookup', [DispatchGetController::class, 'lookup'])->name('dispatches.lookup');
            Route::post('/', DispatchPostController::class)->name('dispatches.store');
            Route::get('/{id}', [DispatchGetController::class, 'show'])->where('id', $uuid)->name('dispatches.show');
            Route::get('/{id}/edit', [DispatchGetController::class, 'edit'])->where('id', $uuid)->name('dispatches.edit');
            Route::put('/{id}', DispatchPutController::class)->where('id', $uuid)->name('dispatches.update');
            Route::put('/{id}/status', DispatchUpdateStatusController::class)->where('id', $uuid)->name('dispatches.update-status');
            /** El resultado del viaje: se registra una vez, sobre un despacho confirmado. */
            Route::put('/{id}/delivery', DispatchDeliveryController::class)->where('id', $uuid)->name('dispatches.delivery');
        });
    });
