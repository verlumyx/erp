<?php

declare(strict_types=1);

use App\Modules\SupplierAdvance\Controllers\SupplierAdvanceGetController;
use App\Modules\SupplierAdvance\Controllers\SupplierAdvancePostController;
use App\Modules\SupplierAdvance\Controllers\SupplierAdvancePutController;
use App\Modules\SupplierAdvance\Controllers\SupplierAdvanceUpdateStatusController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('supplier-advances')->group(function () use ($uuid) {
            Route::get('/', [SupplierAdvanceGetController::class, 'index'])->name('supplier-advances.index');
            Route::get('/create', [SupplierAdvanceGetController::class, 'create'])->name('supplier-advances.create');
            Route::get('/lookup', [SupplierAdvanceGetController::class, 'lookup'])->name('supplier-advances.lookup');
            Route::post('/', SupplierAdvancePostController::class)->name('supplier-advances.store');
            Route::get('/{id}', [SupplierAdvanceGetController::class, 'show'])->where('id', $uuid)->name('supplier-advances.show');
            Route::get('/{id}/edit', [SupplierAdvanceGetController::class, 'edit'])->where('id', $uuid)->name('supplier-advances.edit');
            Route::put('/{id}', SupplierAdvancePutController::class)->where('id', $uuid)->name('supplier-advances.update');
            Route::put('/{id}/status', SupplierAdvanceUpdateStatusController::class)->where('id', $uuid)->name('supplier-advances.update-status');
        });
    });
