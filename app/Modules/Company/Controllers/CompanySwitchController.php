<?php

declare(strict_types=1);

namespace App\Modules\Company\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanySwitchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'string', 'uuid'],
        ]);

        $user = $request->user();

        $belongs = $user->companies()->where('company_id', $validated['company_id'])->exists();

        if (! $belongs) {
            abort(403, 'No tienes acceso a esta empresa.');
        }

        session(['current_company_id' => $validated['company_id']]);

        return redirect("/{$validated['company_id']}/dashboard");
    }
}
