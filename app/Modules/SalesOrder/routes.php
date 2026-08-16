<?php

declare(strict_types=1);

use App\Modules\SalesOrder\Controllers\SalesOrderGetController;
use App\Modules\SalesOrder\Controllers\SalesOrderPostController;
use App\Modules\SalesOrder\Controllers\SalesOrderPutController;
use App\Modules\SalesOrder\Controllers\SalesOrderUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('sales-orders')->group(function () use ($uuid) {
            Route::get('/', [SalesOrderGetController::class, 'index'])->name('sales-orders.index');
            Route::get('/create', [SalesOrderGetController::class, 'create'])->name('sales-orders.create');
            Route::post('/', SalesOrderPostController::class)->name('sales-orders.store');
            Route::get('/{id}', [SalesOrderGetController::class, 'show'])->where('id', $uuid)->name('sales-orders.show');
            Route::get('/{id}/edit', [SalesOrderGetController::class, 'edit'])->where('id', $uuid)->name('sales-orders.edit');
            Route::put('/{id}', SalesOrderPutController::class)->where('id', $uuid)->name('sales-orders.update');
            Route::put('/{id}/status', SalesOrderUpdateStatusController::class)->where('id', $uuid)->name('sales-orders.update-status');
        });
    });
