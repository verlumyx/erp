<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\User\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stateless company-access guard for the mobile API.
 *
 * Unlike the web EnsureUserBelongsToCompany middleware, this never redirects
 * and never aborts with an Inertia response: it returns clean JSON on failure.
 * On success it stores the company id in the in-memory session so the existing
 * permission/menu logic (which reads session('current_company_id')) can be
 * reused without changes.
 */
class EnsureUserBelongsToCompanyApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = (string) $request->route('company');
        $user = $request->user();

        if (! $user instanceof User || $companyId === '') {
            return response()->json(['message' => 'No tienes acceso a esta empresa.'], 403);
        }

        if (! $this->hasActiveAccess($user, $companyId)) {
            return response()->json(['message' => 'No tienes acceso a esta empresa o está inactiva.'], 403);
        }

        session()->put('current_company_id', $companyId);

        return $next($request);
    }

    private function hasActiveAccess(User $user, string $companyId): bool
    {
        $query = $user->companies()
            ->where('company_id', $companyId)
            ->wherePivot('status', 'active');

        if (! $user->is_system_owner) {
            $query->where('app_companies.status', 'active');
        }

        return $query->exists();
    }
}
