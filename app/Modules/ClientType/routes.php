<?php

declare(strict_types=1);

use App\Modules\ClientType\Controllers\ClientTypeGetController;
use App\Modules\ClientType\Controllers\ClientTypePostController;
use App\Modules\ClientType\Controllers\ClientTypePutController;
use App\Modules\ClientType\Controllers\ClientTypeUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('client-types')->group(function () use ($uuid) {
            Route::get('/', [ClientTypeGetController::class, 'index'])->name('client-types.index');
            Route::get('/create', [ClientTypeGetController::class, 'create'])->name('client-types.create');
            Route::post('/', ClientTypePostController::class)->name('client-types.store');
            Route::get('/{id}', [ClientTypeGetController::class, 'show'])->where('id', $uuid)->name('client-types.show');
            Route::get('/{id}/edit', [ClientTypeGetController::class, 'edit'])->where('id', $uuid)->name('client-types.edit');
            Route::put('/{id}', ClientTypePutController::class)->where('id', $uuid)->name('client-types.update');
            Route::put('/{id}/status', ClientTypeUpdateStatusController::class)->where('id', $uuid)->name('client-types.update-status');
        });
    });
