<?php

declare(strict_types=1);

use App\Modules\Route\Controllers\RouteGetController;
use App\Modules\Route\Controllers\RoutePlanController;
use App\Modules\Route\Controllers\RoutePostController;
use App\Modules\Route\Controllers\RoutePutController;
use App\Modules\Route\Controllers\RouteStopVisitController;
use App\Modules\Route\Controllers\RouteUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('routes')->group(function () use ($uuid) {
            Route::get('/', [RouteGetController::class, 'index'])->name('routes.index');
            Route::get('/create', [RouteGetController::class, 'create'])->name('routes.create');
            Route::get('/lookup', [RouteGetController::class, 'lookup'])->name('routes.lookup');
            Route::post('/', RoutePostController::class)->name('routes.store');
            Route::get('/{id}', [RouteGetController::class, 'show'])->where('id', $uuid)->name('routes.show');
            Route::get('/{id}/edit', [RouteGetController::class, 'edit'])->where('id', $uuid)->name('routes.edit');
            Route::put('/{id}', RoutePutController::class)->where('id', $uuid)->name('routes.update');
            Route::put('/{id}/status', RouteUpdateStatusController::class)->where('id', $uuid)->name('routes.update-status');

            /** Generar las paradas de una fecha desde la plantilla y los despachos. */
            Route::post('/{id}/plan', RoutePlanController::class)->where('id', $uuid)->name('routes.plan');

            /** Lo que pasó en una parada concreta: se registra en la calle. */
            Route::put('/{id}/stops/{stop}', RouteStopVisitController::class)
                ->where(['id' => $uuid, 'stop' => $uuid])
                ->name('routes.stops.visit');
        });
    });
