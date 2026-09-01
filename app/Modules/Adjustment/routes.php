<?php

declare(strict_types=1);

use App\Modules\Adjustment\Controllers\AdjustmentGetController;
use App\Modules\Adjustment\Controllers\AdjustmentPostController;
use App\Modules\Adjustment\Controllers\AdjustmentPutController;
use App\Modules\Adjustment\Controllers\AdjustmentUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('adjustments')->group(function () use ($uuid) {
            Route::get('/', [AdjustmentGetController::class, 'index'])->name('adjustments.index');
            Route::get('/create', [AdjustmentGetController::class, 'create'])->name('adjustments.create');
            Route::get('/stock', [AdjustmentGetController::class, 'stock'])->name('adjustments.stock');
            Route::post('/', AdjustmentPostController::class)->name('adjustments.store');
            Route::get('/{id}', [AdjustmentGetController::class, 'show'])->where('id', $uuid)->name('adjustments.show');
            Route::get('/{id}/edit', [AdjustmentGetController::class, 'edit'])->where('id', $uuid)->name('adjustments.edit');
            Route::put('/{id}', AdjustmentPutController::class)->where('id', $uuid)->name('adjustments.update');
            Route::put('/{id}/status', AdjustmentUpdateStatusController::class)->where('id', $uuid)->name('adjustments.update-status');
        });
    });
