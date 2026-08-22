<?php

declare(strict_types=1);

use App\Modules\Client\Controllers\ClientGetController;
use App\Modules\Client\Controllers\ClientPostController;
use App\Modules\Client\Controllers\ClientPutController;
use App\Modules\Client\Controllers\ClientUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('clients')->group(function () use ($uuid) {
            Route::get('/', [ClientGetController::class, 'index'])->name('clients.index');
            Route::get('/create', [ClientGetController::class, 'create'])->name('clients.create');
            Route::get('/lookup', [ClientGetController::class, 'lookup'])->name('clients.lookup');
            Route::post('/', ClientPostController::class)->name('clients.store');
            Route::get('/{id}', [ClientGetController::class, 'show'])->where('id', $uuid)->name('clients.show');
            Route::get('/{id}/edit', [ClientGetController::class, 'edit'])->where('id', $uuid)->name('clients.edit');
            Route::put('/{id}', ClientPutController::class)->where('id', $uuid)->name('clients.update');
            Route::put('/{id}/status', ClientUpdateStatusController::class)->where('id', $uuid)->name('clients.update-status');
        });
    });
