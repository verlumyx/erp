<?php

declare(strict_types=1);

use App\Modules\Configuration\Controllers\ConfigurationGetController;
use App\Modules\Configuration\Controllers\ConfigurationPutController;
use Illuminate\Support\Facades\Route;

$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

/**
 * Sin `{id}`: la configuración se identifica por la empresa de la URL, que ya
 * valida el middleware de acceso.
 */
Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function (): void {
        Route::get('/configuration', [ConfigurationGetController::class, 'edit'])->name('configuration.edit');
        Route::put('/configuration', ConfigurationPutController::class)->name('configuration.update');
    });
