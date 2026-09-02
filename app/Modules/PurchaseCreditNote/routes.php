<?php

declare(strict_types=1);

use App\Modules\PurchaseCreditNote\Controllers\PurchaseCreditNoteGetController;
use App\Modules\PurchaseCreditNote\Controllers\PurchaseCreditNotePostController;
use App\Modules\PurchaseCreditNote\Controllers\PurchaseCreditNotePutController;
use App\Modules\PurchaseCreditNote\Controllers\PurchaseCreditNoteUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('purchase-credit-notes')->group(function () use ($uuid) {
            Route::get('/', [PurchaseCreditNoteGetController::class, 'index'])->name('purchase-credit-notes.index');
            Route::get('/create', [PurchaseCreditNoteGetController::class, 'create'])->name('purchase-credit-notes.create');
            Route::post('/', PurchaseCreditNotePostController::class)->name('purchase-credit-notes.store');
            Route::get('/lookup', [PurchaseCreditNoteGetController::class, 'lookup'])->name('purchase-credit-notes.lookup');
            Route::get('/{id}', [PurchaseCreditNoteGetController::class, 'show'])->where('id', $uuid)->name('purchase-credit-notes.show');
            Route::get('/{id}/edit', [PurchaseCreditNoteGetController::class, 'edit'])->where('id', $uuid)->name('purchase-credit-notes.edit');
            Route::put('/{id}', PurchaseCreditNotePutController::class)->where('id', $uuid)->name('purchase-credit-notes.update');
            Route::put('/{id}/status', PurchaseCreditNoteUpdateStatusController::class)->where('id', $uuid)->name('purchase-credit-notes.update-status');
        });
    });
