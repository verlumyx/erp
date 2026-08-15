<?php

use App\Modules\Lead\Controllers\LeadGetController;
use App\Modules\Lead\Controllers\LeadPostController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/contact', LeadGetController::class)->name('contact');
    Route::post('/contact', LeadPostController::class)->name('contact.store');
});
