<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Modules\Account\Models\Profile;

class DashboardPlatformsService
{
    /**
     * Perfiles ocupados agrupados por servicio (plataforma), de mayor a menor.
     *
     * @return list<array{id: string, name: string, occupied: int}>
     */
    public function forCompany(string $companyId): array
    {
        return Profile::query()
            ->join('app_accounts', 'app_profiles.account_id', '=', 'app_accounts.id')
            ->join('app_services', 'app_accounts.service_id', '=', 'app_services.id')
            ->where('app_accounts.company_id', $companyId)
            ->where('app_profiles.status', 'occupied')
            ->groupBy('app_services.id', 'app_services.name')
            ->orderByDesc('occupied')
            ->selectRaw('app_services.id as id, app_services.name as name, count(*) as occupied')
            ->get()
            ->map(fn ($row): array => [
                'id' => (string) $row->id,
                'name' => (string) $row->name,
                'occupied' => (int) $row->occupied,
            ])
            ->all();
    }
}
