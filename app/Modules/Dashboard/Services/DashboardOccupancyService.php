<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Modules\Account\Models\Profile;

class DashboardOccupancyService
{
    /**
     * Ocupación del inventario de perfiles de la compañía, desglosada por estado.
     *
     * @return array{occupied: int, available: int, maintenance: int, total: int}
     */
    public function forCompany(string $companyId): array
    {
        $counts = Profile::query()
            ->whereHas('account', fn ($query) => $query->where('company_id', $companyId))
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $occupied = (int) ($counts['occupied'] ?? 0);
        $available = (int) ($counts['available'] ?? 0);
        $maintenance = (int) ($counts['maintenance'] ?? 0);

        return [
            'occupied' => $occupied,
            'available' => $available,
            'maintenance' => $maintenance,
            'total' => $occupied + $available + $maintenance,
        ];
    }
}
