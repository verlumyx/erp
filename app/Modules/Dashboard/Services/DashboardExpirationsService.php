<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Modules\Sale\Models\Sale;

class DashboardExpirationsService
{
    private const LOOKAHEAD_DAYS = 9;

    private const LIMIT = 8;

    /**
     * Próximos vencimientos: ventas activas que vencen pronto o ya vencidas.
     *
     * @return list<array{
     *     id: string,
     *     code: string,
     *     client_name: string,
     *     client_phone: string|null,
     *     service_name: string,
     *     status_key: string,
     *     days: int
     * }>
     */
    public function upcoming(string $companyId): array
    {
        $today = now()->startOfDay();
        $window = $today->copy()->addDays(self::LOOKAHEAD_DAYS)->toDateString();

        return Sale::query()
            ->where('company_id', $companyId)
            ->whereIn('status', [Sale::STATUS_ACTIVE, Sale::STATUS_EXPIRED])
            ->where('end_date', '<=', $window)
            ->with(['client:id,name,phone', 'service:id,name'])
            ->orderBy('end_date')
            ->limit(self::LIMIT)
            ->get()
            ->map(function (Sale $sale) use ($today): array {
                $days = (int) $today->diffInDays($sale->end_date->copy()->startOfDay(), false);

                return [
                    'id' => $sale->id,
                    'code' => $sale->code,
                    'client_name' => $sale->client?->name ?? '—',
                    'client_phone' => $sale->client?->phone,
                    'service_name' => $sale->service?->name ?? '—',
                    'status_key' => $days < 0 ? 'vencido' : ($days <= 5 ? 'porvencer' : 'activo'),
                    'days' => $days,
                ];
            })
            ->all();
    }
}
