<?php

declare(strict_types=1);

use App\Modules\Import\Controllers\ImportGetController;
use App\Modules\Import\Controllers\ImportPostController;
use App\Modules\Import\Controllers\ImportPutController;
use App\Modules\Import\Controllers\ImportUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('imports')->group(function () use ($uuid) {
            Route::get('/', [ImportGetController::class, 'index'])->name('imports.index');
            Route::get('/create', [ImportGetController::class, 'create'])->name('imports.create');
            Route::get('/entries', [ImportGetController::class, 'entries'])->name('imports.entries');
            Route::post('/', ImportPostController::class)->name('imports.store');
            Route::get('/{id}', [ImportGetController::class, 'show'])->where('id', $uuid)->name('imports.show');
            Route::get('/{id}/edit', [ImportGetController::class, 'edit'])->where('id', $uuid)->name('imports.edit');
            Route::put('/{id}', ImportPutController::class)->where('id', $uuid)->name('imports.update');
            Route::put('/{id}/status', ImportUpdateStatusController::class)->where('id', $uuid)->name('imports.update-status');
        });
    });
