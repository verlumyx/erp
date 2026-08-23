<?php

declare(strict_types=1);

use App\Modules\ItemSerial\Controllers\ItemSerialGetController;
use App\Modules\ItemSerial\Controllers\ItemSerialPutController;
use App\Modules\ItemSerial\Controllers\ItemSerialUpdateStatusController;
use Illuminate\Support\Facades\Route;

/**
 * Las series no se crean desde pantalla: nacen en el documento que recibe la
 * mercancía (Entrada, Factura de compra), que llama a `ItemSerialCreateService`.
 * Aquí solo se consultan, se corrigen y se les mueve el estado.
 */
$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('item-serials')->group(function () use ($uuid) {
            Route::get('/', [ItemSerialGetController::class, 'index'])->name('item-serials.index');
            Route::get('/lookup', [ItemSerialGetController::class, 'lookup'])->name('item-serials.lookup');
            Route::get('/{id}', [ItemSerialGetController::class, 'show'])->where('id', $uuid)->name('item-serials.show');
            Route::get('/{id}/edit', [ItemSerialGetController::class, 'edit'])->where('id', $uuid)->name('item-serials.edit');
            Route::put('/{id}', ItemSerialPutController::class)->where('id', $uuid)->name('item-serials.update');
            Route::put('/{id}/status', ItemSerialUpdateStatusController::class)->where('id', $uuid)->name('item-serials.update-status');
        });
    });
