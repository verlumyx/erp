<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Shared\Models\UserCompany;
use App\Modules\User\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $request->route('company');
        $user = $request->user();

        if (! $user || ! $companyId) {
            abort(403);
        }

        $belongs = $user->companies()->where('company_id', $companyId)->exists();

        if (! $belongs) {
            abort(403, 'No tienes acceso a esta empresa.');
        }

        if (! $this->hasActiveAccess($user, (string) $companyId)) {
            $fallbackId = $this->resolveActiveFallbackCompanyId($user);

            if ($fallbackId) {
                $request->session()->put('current_company_id', $fallbackId);

                return redirect("/{$fallbackId}/dashboard")
                    ->with('error', 'La empresa a la que intentaste acceder está inactiva o tu acceso a ella no está activo. Te redirigimos a una empresa activa.');
            }

            abort(403, 'No tienes empresas activas disponibles.');
        }

        $request->session()->put('current_company_id', $companyId);

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

    private function resolveActiveFallbackCompanyId(User $user): ?string
    {
        $query = $user->companies()->wherePivot('status', 'active');

        if (! $user->is_system_owner) {
            $query->where('app_companies.status', 'active');
        }

        $activeCompanies = $query->get(['app_companies.id']);

        if ($activeCompanies->isEmpty()) {
            return null;
        }

        $defaultPivot = UserCompany::where('user_id', $user->id)
            ->where('is_default', true)
            ->first();

        $defaultId = $defaultPivot?->company_id;

        if ($defaultId && $activeCompanies->firstWhere('id', $defaultId)) {
            return $defaultId;
        }

        return $activeCompanies->first()->id;
    }
}
