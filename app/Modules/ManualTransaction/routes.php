<?php

declare(strict_types=1);

use App\Modules\ManualTransaction\Controllers\ManualTransactionApproveController;
use App\Modules\ManualTransaction\Controllers\ManualTransactionCancelController;
use App\Modules\ManualTransaction\Controllers\ManualTransactionGetController;
use App\Modules\ManualTransaction\Controllers\ManualTransactionPostController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('manual-transactions')->group(function () use ($uuid) {
            Route::get('/', [ManualTransactionGetController::class, 'index'])->name('manual-transactions.index');
            Route::get('/create', [ManualTransactionGetController::class, 'create'])->name('manual-transactions.create');
            Route::post('/', ManualTransactionPostController::class)->name('manual-transactions.store');
            Route::get('/{id}', [ManualTransactionGetController::class, 'show'])->where('id', $uuid)->name('manual-transactions.show');
            Route::post('/{id}/approve', ManualTransactionApproveController::class)->where('id', $uuid)->name('manual-transactions.approve');
            Route::post('/{id}/cancel', ManualTransactionCancelController::class)->where('id', $uuid)->name('manual-transactions.cancel');
        });
    });
