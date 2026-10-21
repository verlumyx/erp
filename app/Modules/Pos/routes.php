<?php

declare(strict_types=1);

use App\Modules\Pos\Controllers\PosCashMovementPostController;
use App\Modules\Pos\Controllers\PosRegisterGetController;
use App\Modules\Pos\Controllers\PosRegisterPostController;
use App\Modules\Pos\Controllers\PosRegisterPutController;
use App\Modules\Pos\Controllers\PosRegisterUpdateStatusController;
use App\Modules\Pos\Controllers\PosSaleCancelPostController;
use App\Modules\Pos\Controllers\PosSaleCheckoutPostController;
use App\Modules\Pos\Controllers\PosSaleHoldPostController;
use App\Modules\Pos\Controllers\PosSessionClosePostController;
use App\Modules\Pos\Controllers\PosSessionGetController;
use App\Modules\Pos\Controllers\PosSessionOpenPostController;
use App\Modules\Pos\Controllers\PosTerminalController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        // Cajas registradoras (Registers)
        Route::prefix('pos-registers')->group(function () use ($uuid) {
            Route::get('/', [PosRegisterGetController::class, 'index'])->name('pos-registers.index');
            Route::get('/create', [PosRegisterGetController::class, 'create'])->name('pos-registers.create');
            Route::get('/lookup', [PosRegisterGetController::class, 'lookup'])->name('pos-registers.lookup');
            Route::post('/', PosRegisterPostController::class)->name('pos-registers.store');
            Route::get('/{id}/edit', [PosRegisterGetController::class, 'edit'])->where('id', $uuid)->name('pos-registers.edit');
            Route::put('/{id}', PosRegisterPutController::class)->where('id', $uuid)->name('pos-registers.update');
            Route::put('/{id}/status', PosRegisterUpdateStatusController::class)->where('id', $uuid)->name('pos-registers.update-status');
        });

        // Sesiones y Cortes de caja (Sessions)
        Route::prefix('pos-sessions')->group(function () use ($uuid) {
            Route::get('/', [PosSessionGetController::class, 'index'])->name('pos-sessions.index');
            Route::get('/open', [PosSessionGetController::class, 'openForm'])->name('pos-sessions.open');
            Route::post('/open', PosSessionOpenPostController::class)->name('pos-sessions.open.store');
            Route::get('/current', [PosSessionGetController::class, 'current'])->name('pos-sessions.current');
            Route::get('/{id}', [PosSessionGetController::class, 'show'])->where('id', $uuid)->name('pos-sessions.show');
            Route::get('/{id}/reading-x', [PosSessionGetController::class, 'readingX'])->where('id', $uuid)->name('pos-sessions.reading-x');
            Route::get('/{id}/pdf', [PosSessionGetController::class, 'pdf'])->where('id', $uuid)->name('pos-sessions.pdf');
            Route::post('/{id}/close', PosSessionClosePostController::class)->where('id', $uuid)->name('pos-sessions.close');
            Route::post('/{id}/cash-movements', PosCashMovementPostController::class)->where('id', $uuid)->name('pos-sessions.cash-movements.store');
        });

        // Terminal POS & Operaciones de Venta
        Route::prefix('pos')->group(function () use ($uuid) {
            Route::get('/', PosTerminalController::class)->name('pos.terminal');
            Route::post('/checkout', PosSaleCheckoutPostController::class)->name('pos.checkout');
            Route::post('/hold', [PosSaleHoldPostController::class, 'hold'])->name('pos.hold');
            Route::get('/held/{sessionId}', [PosSaleHoldPostController::class, 'list'])->where('sessionId', $uuid)->name('pos.held.list');
            Route::delete('/held/{id}', [PosSaleHoldPostController::class, 'discard'])->where('id', $uuid)->name('pos.held.discard');
            Route::post('/sales/{id}/cancel', PosSaleCancelPostController::class)->where('id', $uuid)->name('pos.sales.cancel');
        });
    });
