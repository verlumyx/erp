<?php

declare(strict_types=1);

use App\Modules\Account\Controllers\AccountCredentialsController;
use App\Modules\Account\Controllers\AccountGetController;
use App\Modules\Account\Controllers\AccountPostController;
use App\Modules\Account\Controllers\AccountPutController;
use App\Modules\Account\Controllers\AccountRenewController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('accounts')->group(function () use ($uuid) {
            Route::get('/', [AccountGetController::class, 'index'])->name('accounts.index');
            Route::get('/create', [AccountGetController::class, 'create'])->name('accounts.create');
            Route::post('/', AccountPostController::class)->name('accounts.store');
            Route::get('/{id}', [AccountGetController::class, 'show'])->where('id', $uuid)->name('accounts.show');
            Route::get('/{id}/edit', [AccountGetController::class, 'edit'])->where('id', $uuid)->name('accounts.edit');
            Route::get('/{id}/credentials', AccountCredentialsController::class)->where('id', $uuid)->name('accounts.credentials');
            Route::put('/{id}', AccountPutController::class)->where('id', $uuid)->name('accounts.update');
            Route::post('/{id}/renew', AccountRenewController::class)->where('id', $uuid)->name('accounts.renew');
        });
    });
