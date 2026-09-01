<?php

declare(strict_types=1);

use App\Modules\SalesReturn\Controllers\SalesReturnGetController;
use App\Modules\SalesReturn\Controllers\SalesReturnPostController;
use App\Modules\SalesReturn\Controllers\SalesReturnPutController;
use App\Modules\SalesReturn\Controllers\SalesReturnUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('sales-returns')->group(function () use ($uuid) {
            Route::get('/', [SalesReturnGetController::class, 'index'])->name('sales-returns.index');
            Route::get('/create', [SalesReturnGetController::class, 'create'])->name('sales-returns.create');
            Route::get('/lookup', [SalesReturnGetController::class, 'lookup'])->name('sales-returns.lookup');
            Route::post('/', SalesReturnPostController::class)->name('sales-returns.store');
            Route::get('/{id}', [SalesReturnGetController::class, 'show'])->where('id', $uuid)->name('sales-returns.show');
            Route::get('/{id}/edit', [SalesReturnGetController::class, 'edit'])->where('id', $uuid)->name('sales-returns.edit');
            Route::put('/{id}', SalesReturnPutController::class)->where('id', $uuid)->name('sales-returns.update');
            Route::put('/{id}/status', SalesReturnUpdateStatusController::class)->where('id', $uuid)->name('sales-returns.update-status');
        });
    });
