<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Modules\Sale\Models\Sale;
use App\Modules\Transaction\Models\Transaction;

class DashboardMetricsService
{
    public function __construct(
        private readonly DashboardOccupancyService $occupancy,
    ) {}

    /**
     * Métricas resumidas para las tarjetas superiores del dashboard.
     *
     * @return array{
     *     income_month: float,
     *     expense_month: float,
     *     net_profit: float,
     *     profit_margin_pct: int,
     *     income_trend_pct: float|null,
     *     profit_trend_pct: float|null,
     *     active_profiles: int,
     *     free_profiles: int,
     *     total_profiles: int,
     *     receivable_amount: float,
     *     receivable_clients: int
     * }
     */
    public function forCompany(string $companyId): array
    {
        $now = now();
        $previous = now()->subMonthNoOverflow();

        $incomeMonth = $this->sum($companyId, Transaction::TYPE_INCOME, $now->year, $now->month);
        $expenseMonth = $this->sum($companyId, Transaction::TYPE_EXPENSE, $now->year, $now->month);
        $incomePrev = $this->sum($companyId, Transaction::TYPE_INCOME, $previous->year, $previous->month);
        $expensePrev = $this->sum($companyId, Transaction::TYPE_EXPENSE, $previous->year, $previous->month);

        $netProfit = $incomeMonth - $expenseMonth;
        $netPrev = $incomePrev - $expensePrev;

        $occupancy = $this->occupancy->forCompany($companyId);

        $expired = Sale::query()->where('company_id', $companyId)->expired();

        return [
            'income_month' => $incomeMonth,
            'expense_month' => $expenseMonth,
            'net_profit' => $netProfit,
            'profit_margin_pct' => $incomeMonth > 0 ? (int) round($netProfit / $incomeMonth * 100) : 0,
            'income_trend_pct' => $this->trend($incomeMonth, $incomePrev),
            'profit_trend_pct' => $this->trend($netProfit, $netPrev),
            'active_profiles' => $occupancy['occupied'],
            'free_profiles' => $occupancy['available'],
            'total_profiles' => $occupancy['total'],
            'receivable_amount' => (float) (clone $expired)->sum('price'),
            'receivable_clients' => (int) (clone $expired)->distinct()->count('client_id'),
        ];
    }

    private function sum(string $companyId, string $type, int $year, int $month): float
    {
        return (float) Transaction::query()
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->ofMonth($year, $month)
            ->sum('amount');
    }

    /**
     * Variación porcentual respecto del periodo anterior, o null si no es comparable.
     */
    private function trend(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }
}
