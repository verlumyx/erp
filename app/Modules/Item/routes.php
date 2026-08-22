<?php

declare(strict_types=1);

use App\Modules\Item\Controllers\ItemGetController;
use App\Modules\Item\Controllers\ItemPostController;
use App\Modules\Item\Controllers\ItemPutController;
use App\Modules\Item\Controllers\ItemUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('items')->group(function () use ($uuid) {
            Route::get('/', [ItemGetController::class, 'index'])->name('items.index');
            Route::get('/create', [ItemGetController::class, 'create'])->name('items.create');
            Route::get('/lookup', [ItemGetController::class, 'lookup'])->name('items.lookup');
            Route::post('/', ItemPostController::class)->name('items.store');
            Route::get('/{id}', [ItemGetController::class, 'show'])->where('id', $uuid)->name('items.show');
            Route::get('/{id}/edit', [ItemGetController::class, 'edit'])->where('id', $uuid)->name('items.edit');
            Route::put('/{id}', ItemPutController::class)->where('id', $uuid)->name('items.update');
            Route::put('/{id}/status', ItemUpdateStatusController::class)->where('id', $uuid)->name('items.update-status');
        });
    });
