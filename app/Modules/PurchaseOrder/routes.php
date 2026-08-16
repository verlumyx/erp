<?php

declare(strict_types=1);

use App\Modules\PurchaseOrder\Controllers\PurchaseOrderGetController;
use App\Modules\PurchaseOrder\Controllers\PurchaseOrderPostController;
use App\Modules\PurchaseOrder\Controllers\PurchaseOrderPutController;
use App\Modules\PurchaseOrder\Controllers\PurchaseOrderUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('purchase-orders')->group(function () use ($uuid) {
            Route::get('/', [PurchaseOrderGetController::class, 'index'])->name('purchase-orders.index');
            Route::get('/create', [PurchaseOrderGetController::class, 'create'])->name('purchase-orders.create');
            Route::post('/', PurchaseOrderPostController::class)->name('purchase-orders.store');
            Route::get('/{id}', [PurchaseOrderGetController::class, 'show'])->where('id', $uuid)->name('purchase-orders.show');
            Route::get('/{id}/edit', [PurchaseOrderGetController::class, 'edit'])->where('id', $uuid)->name('purchase-orders.edit');
            Route::put('/{id}', PurchaseOrderPutController::class)->where('id', $uuid)->name('purchase-orders.update');
            Route::put('/{id}/status', PurchaseOrderUpdateStatusController::class)->where('id', $uuid)->name('purchase-orders.update-status');
        });
    });
