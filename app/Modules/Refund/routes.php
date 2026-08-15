<?php

declare(strict_types=1);

use App\Modules\Refund\Controllers\RefundApproveController;
use App\Modules\Refund\Controllers\RefundGetController;
use App\Modules\Refund\Controllers\RefundPostController;
use App\Modules\Refund\Controllers\RefundPutController;
use App\Modules\Refund\Controllers\RefundRejectController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('refunds')->group(function () use ($uuid) {
            Route::get('/', [RefundGetController::class, 'index'])->name('refunds.index');
            Route::get('/create', [RefundGetController::class, 'create'])->name('refunds.create');
            Route::post('/', RefundPostController::class)->name('refunds.store');
            Route::get('/{id}', [RefundGetController::class, 'show'])->where('id', $uuid)->name('refunds.show');
            Route::put('/{id}', RefundPutController::class)->where('id', $uuid)->name('refunds.update');
            Route::post('/{id}/approve', RefundApproveController::class)->where('id', $uuid)->name('refunds.approve');
            Route::post('/{id}/reject', RefundRejectController::class)->where('id', $uuid)->name('refunds.reject');
        });
    });
