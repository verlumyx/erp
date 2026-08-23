<?php

declare(strict_types=1);

use App\Modules\PurchaseReturn\Controllers\PurchaseReturnGetController;
use App\Modules\PurchaseReturn\Controllers\PurchaseReturnPostController;
use App\Modules\PurchaseReturn\Controllers\PurchaseReturnPutController;
use App\Modules\PurchaseReturn\Controllers\PurchaseReturnUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('purchase-returns')->group(function () use ($uuid) {
            Route::get('/', [PurchaseReturnGetController::class, 'index'])->name('purchase-returns.index');
            Route::get('/create', [PurchaseReturnGetController::class, 'create'])->name('purchase-returns.create');
            Route::get('/lookup', [PurchaseReturnGetController::class, 'lookup'])->name('purchase-returns.lookup');
            Route::post('/', PurchaseReturnPostController::class)->name('purchase-returns.store');
            Route::get('/{id}', [PurchaseReturnGetController::class, 'show'])->where('id', $uuid)->name('purchase-returns.show');
            Route::get('/{id}/edit', [PurchaseReturnGetController::class, 'edit'])->where('id', $uuid)->name('purchase-returns.edit');
            Route::put('/{id}', PurchaseReturnPutController::class)->where('id', $uuid)->name('purchase-returns.update');
            Route::put('/{id}/status', PurchaseReturnUpdateStatusController::class)->where('id', $uuid)->name('purchase-returns.update-status');
        });
    });
