<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Controllers\ExchangeRateGetController;
use App\Modules\ExchangeRate\Controllers\ExchangeRatePostController;
use App\Modules\ExchangeRate\Controllers\ExchangeRatePutController;
use App\Modules\ExchangeRate\Controllers\ExchangeRateUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('exchange-rates')->group(function () use ($uuid) {
            Route::get('/', [ExchangeRateGetController::class, 'index'])->name('exchange-rates.index');
            Route::get('/create', [ExchangeRateGetController::class, 'create'])->name('exchange-rates.create');
            Route::post('/', ExchangeRatePostController::class)->name('exchange-rates.store');
            Route::get('/{id}', [ExchangeRateGetController::class, 'show'])->where('id', $uuid)->name('exchange-rates.show');
            Route::get('/{id}/edit', [ExchangeRateGetController::class, 'edit'])->where('id', $uuid)->name('exchange-rates.edit');
            Route::put('/{id}', ExchangeRatePutController::class)->where('id', $uuid)->name('exchange-rates.update');
            Route::put('/{id}/status', ExchangeRateUpdateStatusController::class)->where('id', $uuid)->name('exchange-rates.update-status');
        });
    });
