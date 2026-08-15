<?php

declare(strict_types=1);

use App\Modules\Role\Controllers\RoleGetController;
use App\Modules\Role\Controllers\RolePostController;
use App\Modules\Role\Controllers\RolePutController;
use App\Modules\Role\Controllers\RoleUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('roles')->group(function () use ($uuid) {
            Route::get('/', [RoleGetController::class, 'index'])->name('roles.index');
            Route::get('/create', [RoleGetController::class, 'create'])->name('roles.create');
            Route::post('/', RolePostController::class)->name('roles.store');
            Route::get('/{id}', [RoleGetController::class, 'show'])->where('id', $uuid)->name('roles.show');
            Route::get('/{id}/edit', [RoleGetController::class, 'edit'])->where('id', $uuid)->name('roles.edit');
            Route::put('/{id}', RolePutController::class)->where('id', $uuid)->name('roles.update');
            Route::put('/{id}/status', RoleUpdateStatusController::class)->where('id', $uuid)->name('roles.update-status');
        });
    });
