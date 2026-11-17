<?php

declare(strict_types=1);

use App\Modules\Report\Controllers\AccountsPayableReportGetController;
use App\Modules\Report\Controllers\AccountsReceivableReportGetController;
use App\Modules\Report\Controllers\IncomeExpenseReportGetController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}/reports')
    ->where(['company' => $uuid])
    ->group(function () {
        Route::get('/accounts-receivable', [AccountsReceivableReportGetController::class, 'index'])->name('reports.accounts-receivable');
        Route::get('/accounts-payable', [AccountsPayableReportGetController::class, 'index'])->name('reports.accounts-payable');
        Route::get('/income-expense', [IncomeExpenseReportGetController::class, 'index'])->name('reports.income-expense');
    });
