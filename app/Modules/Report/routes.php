<?php

declare(strict_types=1);

use App\Modules\Report\Controllers\ExpirationGetController;
use App\Modules\Report\Controllers\IncomeExpenseGetController;
use App\Modules\Report\Controllers\MovementGetController;
use App\Modules\Report\Controllers\ServicePlanGetController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () {
        Route::prefix('reports')->group(function () {
            Route::get('/movements', [MovementGetController::class, 'index'])->name('reports.movements.index');
            Route::get('/income-expenses', [IncomeExpenseGetController::class, 'index'])->name('reports.income-expenses.index');
            Route::get('/service-plan', [ServicePlanGetController::class, 'index'])->name('reports.service-plan.index');
            Route::get('/expirations', [ExpirationGetController::class, 'index'])->name('reports.expirations.index');
        });
    });
