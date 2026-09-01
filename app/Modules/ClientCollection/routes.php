<?php

declare(strict_types=1);

use App\Modules\ClientCollection\Controllers\ClientCollectionGetController;
use App\Modules\ClientCollection\Controllers\ClientCollectionPostController;
use App\Modules\ClientCollection\Controllers\ClientCollectionPutController;
use App\Modules\ClientCollection\Controllers\ClientCollectionUpdateCheckStatusController;
use App\Modules\ClientCollection\Controllers\ClientCollectionUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('client-collections')->group(function () use ($uuid) {
            Route::get('/', [ClientCollectionGetController::class, 'index'])->name('client-collections.index');
            Route::get('/create', [ClientCollectionGetController::class, 'create'])->name('client-collections.create');
            Route::post('/', ClientCollectionPostController::class)->name('client-collections.store');
            Route::get('/{id}', [ClientCollectionGetController::class, 'show'])->where('id', $uuid)->name('client-collections.show');
            Route::get('/{id}/edit', [ClientCollectionGetController::class, 'edit'])->where('id', $uuid)->name('client-collections.edit');
            Route::put('/{id}', ClientCollectionPutController::class)->where('id', $uuid)->name('client-collections.update');
            Route::put('/{id}/status', ClientCollectionUpdateStatusController::class)->where('id', $uuid)->name('client-collections.update-status');
            Route::put('/{id}/check-status', ClientCollectionUpdateCheckStatusController::class)->where('id', $uuid)->name('client-collections.update-check-status');
        });
    });
