<?php

declare(strict_types=1);

use App\Modules\Dashboard\Controllers\DashboardGetController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () {
        Route::get('/dashboard', [DashboardGetController::class, 'index'])->name('company.dashboard');
    });
