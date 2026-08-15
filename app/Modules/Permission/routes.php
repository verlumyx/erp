<?php

declare(strict_types=1);

use App\Modules\Permission\Controllers\PermissionGetController;
use App\Modules\Permission\Controllers\PermissionPostController;
use App\Modules\Permission\Controllers\PermissionPutController;
use App\Modules\Permission\Controllers\PermissionUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('permissions')->group(function () use ($uuid) {
            Route::get('/', [PermissionGetController::class, 'index'])->name('permissions.index');
            Route::get('/create', [PermissionGetController::class, 'create'])->name('permissions.create');
            Route::post('/', PermissionPostController::class)->name('permissions.store');
            Route::get('/{id}', [PermissionGetController::class, 'show'])->where('id', $uuid)->name('permissions.show');
            Route::get('/{id}/edit', [PermissionGetController::class, 'edit'])->where('id', $uuid)->name('permissions.edit');
            Route::put('/{id}', PermissionPutController::class)->where('id', $uuid)->name('permissions.update');
            Route::put('/{id}/status', PermissionUpdateStatusController::class)->where('id', $uuid)->name('permissions.update-status');
        });
    });
