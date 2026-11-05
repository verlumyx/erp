<?php

declare(strict_types=1);

use App\Modules\Lead\Controllers\DemoGetController;
use App\Modules\Lead\Controllers\DemoPostController;
use App\Modules\Lead\Controllers\LeadGetController;
use App\Modules\Lead\Controllers\LeadPostController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/contact', LeadGetController::class)->name('contact');
    Route::post('/contact', LeadPostController::class)->name('contact.store');

    Route::get('/demo', DemoGetController::class)->name('demo');
    Route::post('/demo', DemoPostController::class)->name('demo.store');
});
