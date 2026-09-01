<?php

declare(strict_types=1);

use App\Modules\Transfer\Controllers\TransferGetController;
use App\Modules\Transfer\Controllers\TransferPostController;
use App\Modules\Transfer\Controllers\TransferPutController;
use App\Modules\Transfer\Controllers\TransferReceiptController;
use App\Modules\Transfer\Controllers\TransferUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('transfers')->group(function () use ($uuid) {
            Route::get('/', [TransferGetController::class, 'index'])->name('transfers.index');
            Route::get('/create', [TransferGetController::class, 'create'])->name('transfers.create');
            Route::get('/lookup', [TransferGetController::class, 'lookup'])->name('transfers.lookup');
            Route::post('/', TransferPostController::class)->name('transfers.store');
            Route::get('/{id}', [TransferGetController::class, 'show'])->where('id', $uuid)->name('transfers.show');
            Route::get('/{id}/edit', [TransferGetController::class, 'edit'])->where('id', $uuid)->name('transfers.edit');
            Route::put('/{id}', TransferPutController::class)->where('id', $uuid)->name('transfers.update');
            Route::put('/{id}/status', TransferUpdateStatusController::class)->where('id', $uuid)->name('transfers.update-status');
            /** La llegada al destino: se registra una vez, sobre un traslado confirmado. */
            Route::put('/{id}/receipt', TransferReceiptController::class)->where('id', $uuid)->name('transfers.receipt');
        });
    });
