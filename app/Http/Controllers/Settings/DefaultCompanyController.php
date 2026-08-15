<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Modules\Shared\Models\UserCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DefaultCompanyController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/company');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'company_id' => ['required', 'string', 'uuid'],
        ]);

        $companyId = $request->input('company_id');

        $pivot = UserCompany::where('user_id', $request->user()->id)
            ->where('company_id', $companyId)
            ->first();

        abort_unless($pivot !== null, 403);

        UserCompany::where('user_id', $request->user()->id)
            ->update(['is_default' => false]);

        $pivot->update(['is_default' => true]);

        return to_route('settings.company');
    }
}
