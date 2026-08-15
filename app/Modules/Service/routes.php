<?php

declare(strict_types=1);

use App\Modules\Service\Controllers\ServiceGetController;
use App\Modules\Service\Controllers\ServicePostController;
use App\Modules\Service\Controllers\ServicePutController;
use App\Modules\Service\Controllers\ServiceUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('services')->group(function () use ($uuid) {
            Route::get('/', [ServiceGetController::class, 'index'])->name('services.index');
            Route::get('/create', [ServiceGetController::class, 'create'])->name('services.create');
            Route::post('/', ServicePostController::class)->name('services.store');
            Route::get('/{id}', [ServiceGetController::class, 'show'])->where('id', $uuid)->name('services.show');
            Route::get('/{id}/edit', [ServiceGetController::class, 'edit'])->where('id', $uuid)->name('services.edit');
            Route::put('/{id}', ServicePutController::class)->where('id', $uuid)->name('services.update');
            Route::put('/{id}/status', ServiceUpdateStatusController::class)->where('id', $uuid)->name('services.update-status');
        });
    });
