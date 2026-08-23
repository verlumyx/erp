<?php

declare(strict_types=1);

use App\Modules\ItemStock\Controllers\ItemStockGetController;
use App\Modules\ItemStock\Controllers\ItemStockUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

/**
 * Existencias no tiene `create`, `store` ni `update`: el saldo es derivado y lo
 * mueven los documentos, no un formulario.
 */
Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('item-stocks')->group(function () use ($uuid) {
            Route::get('/', [ItemStockGetController::class, 'index'])->name('item-stocks.index');
            Route::get('/{id}', [ItemStockGetController::class, 'show'])->where('id', $uuid)->name('item-stocks.show');
            Route::put('/{id}/status', ItemStockUpdateStatusController::class)->where('id', $uuid)->name('item-stocks.update-status');
        });
    });
