<?php

declare(strict_types=1);

use App\Modules\Plan\Controllers\PlanGetController;
use App\Modules\Plan\Controllers\PlanPostController;
use App\Modules\Plan\Controllers\PlanPutController;
use App\Modules\Plan\Controllers\PlanUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('plans')->group(function () use ($uuid) {
            Route::get('/', [PlanGetController::class, 'index'])->name('plans.index');
            Route::get('/create', [PlanGetController::class, 'create'])->name('plans.create');
            Route::post('/', PlanPostController::class)->name('plans.store');
            Route::get('/{id}', [PlanGetController::class, 'show'])->where('id', $uuid)->name('plans.show');
            Route::get('/{id}/edit', [PlanGetController::class, 'edit'])->where('id', $uuid)->name('plans.edit');
            Route::put('/{id}', PlanPutController::class)->where('id', $uuid)->name('plans.update');
            Route::put('/{id}/status', PlanUpdateStatusController::class)->where('id', $uuid)->name('plans.update-status');
        });
    });
