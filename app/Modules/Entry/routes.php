<?php

declare(strict_types=1);

use App\Modules\Entry\Controllers\EntryGetController;
use App\Modules\Entry\Controllers\EntryPostController;
use App\Modules\Entry\Controllers\EntryPutController;
use App\Modules\Entry\Controllers\EntryUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('entries')->group(function () use ($uuid) {
            Route::get('/', [EntryGetController::class, 'index'])->name('entries.index');
            Route::get('/create', [EntryGetController::class, 'create'])->name('entries.create');
            Route::get('/lookup', [EntryGetController::class, 'lookup'])->name('entries.lookup');
            Route::post('/', EntryPostController::class)->name('entries.store');
            Route::get('/{id}', [EntryGetController::class, 'show'])->where('id', $uuid)->name('entries.show');
            Route::get('/{id}/edit', [EntryGetController::class, 'edit'])->where('id', $uuid)->name('entries.edit');
            Route::put('/{id}', EntryPutController::class)->where('id', $uuid)->name('entries.update');
            Route::put('/{id}/status', EntryUpdateStatusController::class)->where('id', $uuid)->name('entries.update-status');
        });
    });
