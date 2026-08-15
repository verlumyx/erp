<?php

declare(strict_types=1);

use App\Modules\Auth\Controllers\CompanyContextController;
use App\Modules\Auth\Controllers\LoginController;
use App\Modules\Auth\Controllers\LogoutController;
use App\Modules\Auth\Controllers\MeController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

/*
 * Stateless API routes for the mobile app. Authentication is token-based
 * (Sanctum Bearer tokens) — no session/cookie or {company} prefix here.
 */
Route::prefix('api')->group(function () use ($uuid) {
    Route::post('login', LoginController::class)
        ->middleware('throttle:10,1')
        ->name('api.login');

    Route::middleware('auth:sanctum')->group(function () use ($uuid) {
        Route::post('logout', LogoutController::class)->name('api.logout');
        Route::get('me', MeController::class)->name('api.me');

        Route::middleware('company.access.api')->group(function () use ($uuid) {
            Route::get('companies/{company}/context', CompanyContextController::class)
                ->where('company', $uuid)
                ->name('api.companies.context');
        });
    });
});
