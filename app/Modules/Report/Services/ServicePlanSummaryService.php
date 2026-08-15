<?php

declare(strict_types=1);

namespace App\Modules\Report\Services;

use App\Modules\Plan\Models\Plan;
use App\Modules\Sale\Models\Sale;
use App\Modules\Service\Models\Service;
use Illuminate\Database\Eloquent\Builder;

class ServicePlanSummaryService
{
    public const GROUP_SERVICE = 'service';

    public const GROUP_PLAN = 'plan';

    /**
     * Agrega las ventas de una compañía por servicio o por plan según los filtros.
     *
     * @param  array{ date_from: string, date_to: string, group_by: string, service_id: ?string, status: ?string, capacity: ?string, limit: int, offset: int }  $filters
     * @return array{ data: list<array<string, mixed>>, summary: array<string, mixed>, total: int }
     */
    public function execute(array $filters, string $companyId): array
    {
        $groupColumn = $filters['group_by'] === self::GROUP_PLAN ? 'plan_id' : 'service_id';

        $totals = $this->totals($filters, $companyId);
        $totalRevenue = (float) $totals->total_revenue;
        $totalSales = (int) $totals->total_sales;

        $groupCount = (int) $this->baseQuery($filters, $companyId)
            ->whereNotNull($groupColumn)
            ->distinct()
            ->count($groupColumn);

        $rows = $this->baseQuery($filters, $companyId)
            ->whereNotNull($groupColumn)
            ->selectRaw($groupColumn.' as group_id')
            ->selectRaw('COUNT(*) as sales_count')
            ->selectRaw('COALESCE(SUM(price), 0) as revenue')
            ->groupBy($groupColumn)
            ->orderByDesc('revenue')
            ->limit($filters['limit'])
            ->offset($filters['offset'])
            ->get();

        $data = $this->hydrateRows($rows, $filters['group_by'], $companyId, $totalRevenue);

        return [
            'data' => $data,
            'summary' => [
                'total_sales' => $totalSales,
                'total_revenue' => $totalRevenue,
                'avg_ticket' => $totalSales > 0 ? round($totalRevenue / $totalSales, 2) : 0.0,
                'profile_count' => (int) $totals->profile_count,
                'full_account_count' => (int) $totals->full_account_count,
                'top_label' => $this->topLabel($filters, $companyId, $groupColumn),
            ],
            'total' => $groupCount,
        ];
    }

    /**
     * Query base con los filtros comunes (compañía, rango de fechas y opcionales).
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Sale>
     */
    private function baseQuery(array $filters, string $companyId): Builder
    {
        return Sale::query()
            ->where('company_id', $companyId)
            ->whereDate('created_at', '>=', $filters['date_from'])
            ->whereDate('created_at', '<=', $filters['date_to'])
            ->when($filters['service_id'], fn (Builder $query, string $serviceId) => $query->ofService($serviceId))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['capacity'], fn (Builder $query, string $capacity) => $query->where('capacity', $capacity));
    }

    /**
     * Totales globales del periodo (sin agrupar).
     *
     * @param  array<string, mixed>  $filters
     */
    private function totals(array $filters, string $companyId): object
    {
        return $this->baseQuery($filters, $companyId)
            ->selectRaw('COUNT(*) as total_sales')
            ->selectRaw('COALESCE(SUM(price), 0) as total_revenue')
            ->selectRaw("SUM(CASE WHEN capacity = ? THEN 1 ELSE 0 END) as profile_count", [Sale::CAPACITY_PROFILE])
            ->selectRaw("SUM(CASE WHEN capacity = ? THEN 1 ELSE 0 END) as full_account_count", [Sale::CAPACITY_FULL_ACCOUNT])
            ->first();
    }

    /**
     * Etiqueta del servicio/plan con mayor ingreso en el periodo.
     *
     * @param  array<string, mixed>  $filters
     */
    private function topLabel(array $filters, string $companyId, string $groupColumn): ?string
    {
        $top = $this->baseQuery($filters, $companyId)
            ->whereNotNull($groupColumn)
            ->selectRaw($groupColumn.' as group_id')
            ->selectRaw('COALESCE(SUM(price), 0) as revenue')
            ->groupBy($groupColumn)
            ->orderByDesc('revenue')
            ->first();

        if ($top === null) {
            return null;
        }

        $model = $filters['group_by'] === self::GROUP_PLAN
            ? Plan::query()->find($top->group_id)
            : Service::query()->find($top->group_id);

        return $model?->name;
    }

    /**
     * Enriquece las filas agrupadas con código/nombre y, para planes, precio y ROI objetivo.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return list<array<string, mixed>>
     */
    private function hydrateRows(\Illuminate\Support\Collection $rows, string $groupBy, string $companyId, float $totalRevenue): array
    {
        $ids = $rows->pluck('group_id')->all();

        if ($groupBy === self::GROUP_PLAN) {
            $models = Plan::query()->where('company_id', $companyId)->whereIn('id', $ids)->get()->keyBy('id');
        } else {
            $models = Service::query()->where('company_id', $companyId)->whereIn('id', $ids)->get()->keyBy('id');
        }

        return $rows->map(function (object $row) use ($models, $groupBy, $totalRevenue): array {
            $model = $models->get($row->group_id);
            $salesCount = (int) $row->sales_count;
            $revenue = (float) $row->revenue;

            $base = [
                'id' => $row->group_id,
                'code' => $model?->code,
                'name' => $model?->name,
                'sales_count' => $salesCount,
                'revenue' => $revenue,
                'avg_ticket' => $salesCount > 0 ? round($revenue / $salesCount, 2) : 0.0,
                'revenue_pct' => $totalRevenue > 0 ? round($revenue / $totalRevenue * 100, 2) : 0.0,
            ];

            if ($groupBy === self::GROUP_PLAN) {
                $base['sale_price'] = $model !== null ? (float) $model->sale_price : null;
                $base['roi_target_pct'] = $model !== null ? (float) $model->roi_target_pct : null;
            }

            return $base;
        })->all();
    }
}
