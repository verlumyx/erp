<?php

declare(strict_types=1);

use App\Modules\Category\Controllers\CategoryGetController;
use App\Modules\Category\Controllers\CategoryPostController;
use App\Modules\Category\Controllers\CategoryPutController;
use App\Modules\Category\Controllers\CategoryUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('categories')->group(function () use ($uuid) {
            Route::get('/', [CategoryGetController::class, 'index'])->name('categories.index');
            Route::get('/create', [CategoryGetController::class, 'create'])->name('categories.create');
            Route::post('/', CategoryPostController::class)->name('categories.store');
            Route::get('/{id}', [CategoryGetController::class, 'show'])->where('id', $uuid)->name('categories.show');
            Route::get('/{id}/edit', [CategoryGetController::class, 'edit'])->where('id', $uuid)->name('categories.edit');
            Route::put('/{id}', CategoryPutController::class)->where('id', $uuid)->name('categories.update');
            Route::put('/{id}/status', CategoryUpdateStatusController::class)->where('id', $uuid)->name('categories.update-status');
        });
    });
