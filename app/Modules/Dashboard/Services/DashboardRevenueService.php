<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Modules\Transaction\Models\Transaction;

class DashboardRevenueService
{
    private const MONTH_LABELS = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
    ];

    /**
     * Serie de ingresos vs. costo de los últimos 6 meses (del más antiguo al actual).
     *
     * @return list<array{month: string, income: float, expense: float, profit: float}>
     */
    public function lastSixMonths(string $companyId): array
    {
        $start = now()->startOfMonth()->subMonthsNoOverflow(5);

        $buckets = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonthsNoOverflow($i);
            $buckets[$month->format('Y-m')] = [
                'month' => self::MONTH_LABELS[$month->month],
                'income' => 0.0,
                'expense' => 0.0,
            ];
        }

        $transactions = Transaction::query()
            ->where('company_id', $companyId)
            ->where('date', '>=', $start->toDateString())
            ->get(['date', 'type', 'amount']);

        foreach ($transactions as $transaction) {
            $key = $transaction->date->format('Y-m');

            if (! isset($buckets[$key])) {
                continue;
            }

            if ($transaction->type === Transaction::TYPE_INCOME) {
                $buckets[$key]['income'] += (float) $transaction->amount;
            } else {
                $buckets[$key]['expense'] += (float) $transaction->amount;
            }
        }

        return array_values(array_map(
            fn (array $bucket): array => [
                ...$bucket,
                'profit' => $bucket['income'] - $bucket['expense'],
            ],
            $buckets,
        ));
    }
}
