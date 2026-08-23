<?php

declare(strict_types=1);

use App\Modules\ItemLot\Controllers\ItemLotGetController;
use App\Modules\ItemLot\Controllers\ItemLotPutController;
use App\Modules\ItemLot\Controllers\ItemLotUpdateStatusController;
use Illuminate\Support\Facades\Route;

/**
 * Los lotes no se crean desde pantalla: nacen en el documento que recibe la
 * mercancía (Entrada, Factura de compra), que llama a `ItemLotCreateService`.
 * Aquí solo se consultan, se corrigen y se retienen.
 */
$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('item-lots')->group(function () use ($uuid) {
            Route::get('/', [ItemLotGetController::class, 'index'])->name('item-lots.index');
            Route::get('/lookup', [ItemLotGetController::class, 'lookup'])->name('item-lots.lookup');
            Route::get('/{id}', [ItemLotGetController::class, 'show'])->where('id', $uuid)->name('item-lots.show');
            Route::get('/{id}/edit', [ItemLotGetController::class, 'edit'])->where('id', $uuid)->name('item-lots.edit');
            Route::put('/{id}', ItemLotPutController::class)->where('id', $uuid)->name('item-lots.update');
            Route::put('/{id}/status', ItemLotUpdateStatusController::class)->where('id', $uuid)->name('item-lots.update-status');
        });
    });
