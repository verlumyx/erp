<?php

declare(strict_types=1);

use App\Modules\Tax\Controllers\TaxGetController;
use App\Modules\Tax\Controllers\TaxPostController;
use App\Modules\Tax\Controllers\TaxPutController;
use App\Modules\Tax\Controllers\TaxUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('taxes')->group(function () use ($uuid) {
            Route::get('/', [TaxGetController::class, 'index'])->name('taxes.index');
            Route::get('/create', [TaxGetController::class, 'create'])->name('taxes.create');
            Route::post('/', TaxPostController::class)->name('taxes.store');
            Route::get('/{id}', [TaxGetController::class, 'show'])->where('id', $uuid)->name('taxes.show');
            Route::get('/{id}/edit', [TaxGetController::class, 'edit'])->where('id', $uuid)->name('taxes.edit');
            Route::put('/{id}', TaxPutController::class)->where('id', $uuid)->name('taxes.update');
            Route::put('/{id}/status', TaxUpdateStatusController::class)->where('id', $uuid)->name('taxes.update-status');
        });
    });
