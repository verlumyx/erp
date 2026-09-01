<?php

declare(strict_types=1);

use App\Modules\SalesCreditNote\Controllers\SalesCreditNoteGetController;
use App\Modules\SalesCreditNote\Controllers\SalesCreditNotePostController;
use App\Modules\SalesCreditNote\Controllers\SalesCreditNotePutController;
use App\Modules\SalesCreditNote\Controllers\SalesCreditNoteUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('sales-credit-notes')->group(function () use ($uuid) {
            Route::get('/', [SalesCreditNoteGetController::class, 'index'])->name('sales-credit-notes.index');
            Route::get('/create', [SalesCreditNoteGetController::class, 'create'])->name('sales-credit-notes.create');
            Route::post('/', SalesCreditNotePostController::class)->name('sales-credit-notes.store');
            Route::get('/{id}', [SalesCreditNoteGetController::class, 'show'])->where('id', $uuid)->name('sales-credit-notes.show');
            Route::get('/{id}/edit', [SalesCreditNoteGetController::class, 'edit'])->where('id', $uuid)->name('sales-credit-notes.edit');
            Route::put('/{id}', SalesCreditNotePutController::class)->where('id', $uuid)->name('sales-credit-notes.update');
            Route::put('/{id}/status', SalesCreditNoteUpdateStatusController::class)->where('id', $uuid)->name('sales-credit-notes.update-status');
        });
    });
