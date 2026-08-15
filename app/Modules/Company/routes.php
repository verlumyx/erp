<?php

declare(strict_types=1);

use App\Modules\Company\Controllers\CompanyGetController;
use App\Modules\Company\Controllers\CompanyPostController;
use App\Modules\Company\Controllers\CompanyPutController;
use App\Modules\Company\Controllers\CompanySwitchController;
use App\Modules\Company\Controllers\CompanyUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('companies')->group(function () use ($uuid) {
            Route::get('/', [CompanyGetController::class, 'index'])->name('companies.index');
            Route::get('/create', [CompanyGetController::class, 'create'])->name('companies.create');
            Route::post('/', CompanyPostController::class)->name('companies.store');
            Route::get('/{id}', [CompanyGetController::class, 'show'])->where('id', $uuid)->name('companies.show');
            Route::get('/{id}/edit', [CompanyGetController::class, 'edit'])->where('id', $uuid)->name('companies.edit');
            Route::put('/{id}', CompanyPutController::class)->where('id', $uuid)->name('companies.update');
            Route::put('/{id}/status', CompanyUpdateStatusController::class)->where('id', $uuid)->name('companies.update-status');
        });
    });

Route::middleware(['web', 'auth', 'verified'])->post('/company/switch', CompanySwitchController::class)->name('company.switch');
