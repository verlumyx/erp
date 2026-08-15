<?php

declare(strict_types=1);

use App\Modules\Sale\Controllers\SaleCancelController;
use App\Modules\Sale\Controllers\SaleClientSearchController;
use App\Modules\Sale\Controllers\SaleGetController;
use App\Modules\Sale\Controllers\SalePostController;
use App\Modules\Sale\Controllers\SaleReactivateController;
use App\Modules\Sale\Controllers\SaleRenewController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('sales')->group(function () use ($uuid) {
            Route::get('/', [SaleGetController::class, 'index'])->name('sales.index');
            Route::get('/create', [SaleGetController::class, 'create'])->name('sales.create');
            Route::get('/clients/search', SaleClientSearchController::class)->name('sales.clients.search');
            Route::post('/', SalePostController::class)->name('sales.store');
            Route::get('/{id}', [SaleGetController::class, 'show'])->where('id', $uuid)->name('sales.show');
            Route::post('/{id}/renew', SaleRenewController::class)->where('id', $uuid)->name('sales.renew');
            Route::post('/{id}/reactivate', SaleReactivateController::class)->where('id', $uuid)->name('sales.reactivate');
            Route::post('/{id}/cancel', SaleCancelController::class)->where('id', $uuid)->name('sales.cancel');
        });
    });
