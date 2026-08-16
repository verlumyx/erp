<?php

declare(strict_types=1);

use App\Modules\PriceList\Controllers\PriceListGetController;
use App\Modules\PriceList\Controllers\PriceListPostController;
use App\Modules\PriceList\Controllers\PriceListPutController;
use App\Modules\PriceList\Controllers\PriceListUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('price-lists')->group(function () use ($uuid) {
            Route::get('/', [PriceListGetController::class, 'index'])->name('price-lists.index');
            Route::get('/create', [PriceListGetController::class, 'create'])->name('price-lists.create');
            Route::post('/', PriceListPostController::class)->name('price-lists.store');
            Route::get('/{id}', [PriceListGetController::class, 'show'])->where('id', $uuid)->name('price-lists.show');
            Route::get('/{id}/edit', [PriceListGetController::class, 'edit'])->where('id', $uuid)->name('price-lists.edit');
            Route::put('/{id}', PriceListPutController::class)->where('id', $uuid)->name('price-lists.update');
            Route::put('/{id}/status', PriceListUpdateStatusController::class)->where('id', $uuid)->name('price-lists.update-status');
        });
    });
