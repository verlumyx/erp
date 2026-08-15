<?php

use App\Modules\Shared\Models\UserCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

/**
 * Fortify redirects to this route after login.
 * We redirect to the company-prefixed dashboard.
 */
Route::get('dashboard', function (Request $request) {
    $companyId = $request->session()->get('current_company_id');

    if (! $companyId && $request->user()) {
        $defaultPivot = UserCompany::where('user_id', $request->user()->id)
            ->where('is_default', true)
            ->first();

        $companyId = $defaultPivot?->company_id
            ?? $request->user()->companies()->first()?->id;

        if ($companyId) {
            $request->session()->put('current_company_id', $companyId);
        }
    }

    if ($companyId) {
        return redirect("/{$companyId}/dashboard");
    }

    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/*
 * The company-prefixed dashboard route (company.dashboard) lives in the
 * Dashboard module (app/Modules/Dashboard/routes.php).
 */

require __DIR__.'/settings.php';
