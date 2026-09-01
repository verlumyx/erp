<?php

declare(strict_types=1);

use App\Modules\ClientAdvance\Controllers\ClientAdvanceGetController;
use App\Modules\ClientAdvance\Controllers\ClientAdvancePostController;
use App\Modules\ClientAdvance\Controllers\ClientAdvancePutController;
use App\Modules\ClientAdvance\Controllers\ClientAdvanceUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('client-advances')->group(function () use ($uuid) {
            Route::get('/', [ClientAdvanceGetController::class, 'index'])->name('client-advances.index');
            Route::get('/create', [ClientAdvanceGetController::class, 'create'])->name('client-advances.create');
            Route::get('/lookup', [ClientAdvanceGetController::class, 'lookup'])->name('client-advances.lookup');
            Route::post('/', ClientAdvancePostController::class)->name('client-advances.store');
            Route::get('/{id}', [ClientAdvanceGetController::class, 'show'])->where('id', $uuid)->name('client-advances.show');
            Route::get('/{id}/edit', [ClientAdvanceGetController::class, 'edit'])->where('id', $uuid)->name('client-advances.edit');
            Route::put('/{id}', ClientAdvancePutController::class)->where('id', $uuid)->name('client-advances.update');
            Route::put('/{id}/status', ClientAdvanceUpdateStatusController::class)->where('id', $uuid)->name('client-advances.update-status');
        });
    });
