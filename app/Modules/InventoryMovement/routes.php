<?php

declare(strict_types=1);

use App\Modules\InventoryMovement\Controllers\InventoryMovementGetController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

/**
 * El kardex es inmutable: solo `index` y `show`. No hay `create`, `store`,
 * `update` ni `status` porque el movimiento no se captura ni se edita — lo
 * emite el documento que afecta el inventario, y se corrige con la
 * contrapartida que genera su anulación.
 */
Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('inventory-movements')->group(function () use ($uuid) {
            Route::get('/', [InventoryMovementGetController::class, 'index'])->name('inventory-movements.index');
            Route::get('/{id}', [InventoryMovementGetController::class, 'show'])->where('id', $uuid)->name('inventory-movements.show');
        });
    });
