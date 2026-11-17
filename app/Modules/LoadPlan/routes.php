<?php

declare(strict_types=1);

use App\Modules\LoadPlan\Controllers\LoadPlanGetController;
use App\Modules\LoadPlan\Controllers\LoadPlanPostController;
use App\Modules\LoadPlan\Controllers\LoadPlanPutController;
use App\Modules\LoadPlan\Controllers\LoadPlanUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('load-plans')->group(function () use ($uuid) {
            Route::get('/', [LoadPlanGetController::class, 'index'])->name('load-plans.index');
            Route::get('/create', [LoadPlanGetController::class, 'create'])->name('load-plans.create');
            Route::get('/pending-dispatches', [LoadPlanGetController::class, 'pendingDispatches'])->name('load-plans.pending-dispatches');
            Route::post('/', LoadPlanPostController::class)->name('load-plans.store');
            Route::get('/{id}', [LoadPlanGetController::class, 'show'])->where('id', $uuid)->name('load-plans.show');
            Route::get('/{id}/pdf', [LoadPlanGetController::class, 'pdf'])->where('id', $uuid)->name('load-plans.pdf');
            Route::get('/{id}/edit', [LoadPlanGetController::class, 'edit'])->where('id', $uuid)->name('load-plans.edit');
            Route::put('/{id}', LoadPlanPutController::class)->where('id', $uuid)->name('load-plans.update');
            Route::put('/{id}/status', LoadPlanUpdateStatusController::class)->where('id', $uuid)->name('load-plans.update-status');
        });
    });
