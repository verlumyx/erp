<?php

declare(strict_types=1);

use App\Modules\Menu\Controllers\MenuApiController;
use App\Modules\Menu\Controllers\MenuGetController;
use App\Modules\Menu\Controllers\MenuPostController;
use App\Modules\Menu\Controllers\MenuPutController;
use App\Modules\Menu\Controllers\MenuUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

/*
 * Stateless API route for the mobile app (Bearer token + company scope).
 */
Route::prefix('api')
    ->middleware(['auth:sanctum', 'company.access.api'])
    ->group(function () use ($uuid) {
        Route::get('companies/{company}/menu', MenuApiController::class)
            ->where('company', $uuid)
            ->name('api.companies.menu');
    });

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('menus')->group(function () use ($uuid) {
            Route::get('/', [MenuGetController::class, 'index'])->name('menus.index');
            Route::get('/create', [MenuGetController::class, 'create'])->name('menus.create');
            Route::post('/', MenuPostController::class)->name('menus.store');
            Route::get('/{id}', [MenuGetController::class, 'show'])->where('id', $uuid)->name('menus.show');
            Route::get('/{id}/edit', [MenuGetController::class, 'edit'])->where('id', $uuid)->name('menus.edit');
            Route::put('/{id}', MenuPutController::class)->where('id', $uuid)->name('menus.update');
            Route::put('/{id}/status', MenuUpdateStatusController::class)->where('id', $uuid)->name('menus.update-status');
        });
    });
