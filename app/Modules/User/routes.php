<?php

declare(strict_types=1);

use App\Modules\User\Controllers\UserGetController;
use App\Modules\User\Controllers\UserPostController;
use App\Modules\User\Controllers\UserPutController;
use App\Modules\User\Controllers\UserUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('users')->group(function () use ($uuid) {
            Route::get('/', [UserGetController::class, 'index'])->name('users.index');
            Route::get('/create', [UserGetController::class, 'create'])->name('users.create');
            Route::get('/check-email', [UserGetController::class, 'checkEmail'])->name('users.check-email');
            Route::post('/', UserPostController::class)->name('users.store');
            Route::get('/{id}', [UserGetController::class, 'show'])->where('id', $uuid)->name('users.show');
            Route::get('/{id}/edit', [UserGetController::class, 'edit'])->where('id', $uuid)->name('users.edit');
            Route::put('/{id}', UserPutController::class)->where('id', $uuid)->name('users.update');
            Route::put('/{id}/status', UserUpdateStatusController::class)->where('id', $uuid)->name('users.update-status');
        });
    });
