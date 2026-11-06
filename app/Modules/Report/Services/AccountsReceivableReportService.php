<?php

declare(strict_types=1);

namespace App\Modules\Report\Services;

use App\Modules\Client\Models\Client;
use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ExchangeRate\Services\ExchangeRateResolver;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\User\Models\User;
use Carbon\Carbon;

class AccountsReceivableReportService
{
    public function __construct(
        private readonly ExchangeRateResolver $exchangeRateResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function execute(string $companyId, array $filters): array
    {
        $asOfDateStr = (string) ($filters['as_of_date'] ?? now()->toDateString());
        $asOfDate = Carbon::parse($asOfDateStr)->startOfDay();

        $limit = max(1, (int) ($filters['limit'] ?? 15));
        $offset = max(0, (int) ($filters['offset'] ?? 0));
        $clientId = ! empty($filters['client_id']) ? (string) $filters['client_id'] : null;
        $salespersonId = ! empty($filters['salesperson_id']) ? (string) $filters['salesperson_id'] : null;
        $paymentStatus = ! empty($filters['payment_status']) && $filters['payment_status'] !== 'all'
            ? (string) $filters['payment_status']
            : null;

        // Base invoices query: collectible credit invoices with pending balance as of cutoff date
        $baseInvoicesQuery = SalesInvoice::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->where('balance', '>', 0)
            ->whereDate('invoice_date', '<=', $asOfDate);

        if ($clientId) {
            $baseInvoicesQuery->where('client_id', $clientId);
        }

        if ($salespersonId) {
            $baseInvoicesQuery->where('salesperson_id', $salespersonId);
        }

        if ($paymentStatus === 'overdue') {
            $baseInvoicesQuery->whereDate('due_date', '<', $asOfDate);
        } elseif ($paymentStatus === 'pending') {
            $baseInvoicesQuery->where('paid_amount', '=', 0);
        } elseif ($paymentStatus === 'partial') {
            $baseInvoicesQuery->where('paid_amount', '>', 0);
        }

        // Resolución de tasa de cambio oficial de corte a VES
        $rateVes = $this->exchangeRateResolver->tryRateFor($companyId, 'USD', $asOfDateStr) ?? 1.0;

        $isConsulted = filter_var($filters['consulted'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Opciones para selectores de filtro
        $clientOptions = Client::query()
            ->where('company_id', $companyId)
            ->select('id', 'name', 'code', 'document_type', 'document_number')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'value' => $c->id,
                'label' => "{$c->name} ({$c->document_type}-{$c->document_number})",
            ])
            ->all();

        $salespersonOptions = User::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'value' => $u->id,
                'label' => $u->name,
            ])
            ->all();

        if (! $isConsulted) {
            return [
                'consulted' => false,
                'rows' => [],
                'summary' => [
                    'total_gross_debt' => 0.0,
                    'total_advances' => 0.0,
                    'total_credit_notes' => 0.0,
                    'total_net_debt' => 0.0,
                    'total_current' => 0.0,
                    'total_overdue' => 0.0,
                    'total_clients' => 0,
                    'total_invoices' => 0,
                    'exchange_rate' => $rateVes,
                ],
                'meta' => [
                    'total' => 0,
                    'limit' => $limit,
                    'offset' => 0,
                    'has_more' => false,
                ],
                'filters' => [
                    'consulted' => false,
                    'as_of_date' => $asOfDateStr,
                    'client_id' => $clientId,
                    'salesperson_id' => $salespersonId,
                    'payment_status' => $paymentStatus ?? 'all',
                    'currency_view' => $filters['currency_view'] ?? 'base',
                    'limit' => $limit,
                    'offset' => 0,
                ],
                'options' => [
                    'clients' => $clientOptions,
                    'salespersons' => $salespersonOptions,
                ],
            ];
        }

        // 1. Obtener lista paginada de clientes con facturas pendientes
        $clientIdsQuery = (clone $baseInvoicesQuery)->select('client_id')->distinct();
        $totalClients = $clientIdsQuery->count('client_id');
        $pageClientIds = (clone $clientIdsQuery)
            ->offset($offset)
            ->limit($limit)
            ->pluck('client_id')
            ->all();

        $clients = Client::query()
            ->whereIn('id', $pageClientIds)
            ->get()
            ->keyBy('id');

        $rows = [];
        foreach ($pageClientIds as $cId) {
            $client = $clients->get($cId);
            if (! $client) {
                continue;
            }

            // Facturas de este cliente
            $invoices = (clone $baseInvoicesQuery)
                ->where('client_id', $cId)
                ->with(['salesperson:id,name'])
                ->orderBy('due_date', 'asc')
                ->get();

            $current = 0.0;
            $days1To30 = 0.0;
            $days31To60 = 0.0;
            $days61To90 = 0.0;
            $daysOver90 = 0.0;
            $grossDebt = 0.0;
            $grossDebtUsd = 0.0;
            $grossDebtVes = 0.0;

            $formattedInvoices = [];

            foreach ($invoices as $inv) {
                $dueDate = Carbon::parse($inv->due_date)->startOfDay();
                // diffInDays positivo = días de mora respecto a la fecha de corte
                $daysOverdue = (int) $dueDate->diffInDays($asOfDate, false);
                $isOverdue = $daysOverdue > 0;

                $bal = (float) $inv->balance;
                $grossDebt += $bal;

                // Cálculo de contravalores en USD y VES
                if (strtoupper((string) $inv->currency) === 'VES') {
                    $balVes = $bal;
                    $balUsd = $rateVes > 0 ? $bal / $rateVes : 0.0;
                } else {
                    $balUsd = $bal;
                    $invRate = (float) ($inv->exchange_rate ?? $rateVes);
                    $balVes = $bal * ($invRate > 0 ? $invRate : $rateVes);
                }

                $grossDebtUsd += $balUsd;
                $grossDebtVes += $balVes;

                // Tramos de envejecimiento (Aging)
                if (! $isOverdue) {
                    $current += $bal;
                } elseif ($daysOverdue <= 30) {
                    $days1To30 += $bal;
                } elseif ($daysOverdue <= 60) {
                    $days31To60 += $bal;
                } elseif ($daysOverdue <= 90) {
                    $days61To90 += $bal;
                } else {
                    $daysOver90 += $bal;
                }

                $invStatus = $inv->payment_status;
                if ($isOverdue && $invStatus !== 'paid') {
                    $invStatus = 'overdue';
                }

                $formattedInvoices[] = [
                    'id' => $inv->id,
                    'code' => $inv->code,
                    'invoice_series' => $inv->invoice_series,
                    'invoice_number' => $inv->invoice_number,
                    'full_number' => $inv->invoice_series
                        ? "{$inv->invoice_series}-{$inv->invoice_number}"
                        : ($inv->invoice_number ?? $inv->code),
                    'invoice_date' => $inv->invoice_date?->format('Y-m-d'),
                    'due_date' => $inv->due_date?->format('Y-m-d'),
                    'days_credit' => (int) ($inv->payment_term_days ?? 0),
                    'days_overdue' => $daysOverdue,
                    'is_overdue' => $isOverdue,
                    'salesperson_name' => $inv->salesperson?->name ?? '—',
                    'currency' => $inv->currency,
                    'total' => (float) $inv->total,
                    'paid_amount' => (float) $inv->paid_amount,
                    'balance' => $bal,
                    'balance_usd' => round($balUsd, 2),
                    'balance_ves' => round($balVes, 2),
                    'payment_status' => $invStatus,
                ];
            }

            // Deducciones a favor del cliente: anticipos confirmados sin aplicar y notas de crédito abiertas
            $advancesBalance = (float) ClientAdvance::query()
                ->where('company_id', $companyId)
                ->where('client_id', $cId)
                ->where('status', 'confirmed')
                ->where('balance', '>', 0)
                ->sum('balance');

            $creditNotesBalance = (float) SalesCreditNote::query()
                ->where('company_id', $companyId)
                ->where('client_id', $cId)
                ->where('status', 'confirmed')
                ->where('balance', '>', 0)
                ->sum('balance');

            $netDebt = max(0.0, $grossDebt - $advancesBalance - $creditNotesBalance);
            $creditLimit = (float) ($client->credit_limit ?? 0);
            $creditUsagePercent = $creditLimit > 0 ? round(($netDebt / $creditLimit) * 100, 1) : 0.0;

            $rows[] = [
                'client' => [
                    'id' => $client->id,
                    'code' => $client->code,
                    'name' => $client->name,
                    'legal_name' => $client->legal_name,
                    'tax_number' => $client->tax_number ?? "{$client->document_type}-{$client->document_number}",
                    'document_type' => $client->document_type,
                    'document_number' => $client->document_number,
                    'credit_limit' => $creditLimit,
                    'credit_usage_percent' => $creditUsagePercent,
                ],
                'aging' => [
                    'current' => round($current, 2),
                    'days_1_30' => round($days1To30, 2),
                    'days_31_60' => round($days31To60, 2),
                    'days_61_90' => round($days61To90, 2),
                    'days_over_90' => round($daysOver90, 2),
                ],
                'gross_debt' => round($grossDebt, 2),
                'gross_debt_usd' => round($grossDebtUsd, 2),
                'gross_debt_ves' => round($grossDebtVes, 2),
                'advances_balance' => round($advancesBalance, 2),
                'credit_notes_balance' => round($creditNotesBalance, 2),
                'net_debt' => round($netDebt, 2),
                'invoices_count' => count($formattedInvoices),
                'invoices' => $formattedInvoices,
            ];
        }

        // 2. Resumen ejecutivo global de cartera (a la fecha de corte)
        $allInvoices = (clone $baseInvoicesQuery)->get();
        $totalGross = 0.0;
        $totalCurrent = 0.0;
        $totalOverdue = 0.0;

        foreach ($allInvoices as $inv) {
            $bal = (float) $inv->balance;
            $totalGross += $bal;
            $dueDate = Carbon::parse($inv->due_date)->startOfDay();
            if ($dueDate->diffInDays($asOfDate, false) > 0) {
                $totalOverdue += $bal;
            } else {
                $totalCurrent += $bal;
            }
        }

        $allClientIdsWithDebt = (clone $baseInvoicesQuery)->select('client_id')->distinct()->pluck('client_id');
        $totalAdvances = (float) ClientAdvance::query()
            ->where('company_id', $companyId)
            ->whereIn('client_id', $allClientIdsWithDebt)
            ->where('status', 'confirmed')
            ->where('balance', '>', 0)
            ->sum('balance');

        $totalCreditNotes = (float) SalesCreditNote::query()
            ->where('company_id', $companyId)
            ->whereIn('client_id', $allClientIdsWithDebt)
            ->where('status', 'confirmed')
            ->where('balance', '>', 0)
            ->sum('balance');

        $totalNet = max(0.0, $totalGross - $totalAdvances - $totalCreditNotes);

        $summary = [
            'total_gross_debt' => round($totalGross, 2),
            'total_advances' => round($totalAdvances, 2),
            'total_credit_notes' => round($totalCreditNotes, 2),
            'total_net_debt' => round($totalNet, 2),
            'total_current' => round($totalCurrent, 2),
            'total_overdue' => round($totalOverdue, 2),
            'total_clients' => $totalClients,
            'total_invoices' => $allInvoices->count(),
            'exchange_rate' => $rateVes,
        ];

        return [
            'consulted' => true,
            'rows' => $rows,
            'summary' => $summary,
            'meta' => [
                'total' => $totalClients,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => $totalClients > $offset + $limit,
            ],
            'filters' => [
                'consulted' => true,
                'as_of_date' => $asOfDateStr,
                'client_id' => $clientId,
                'salesperson_id' => $salespersonId,
                'payment_status' => $paymentStatus ?? 'all',
                'currency_view' => $filters['currency_view'] ?? 'base',
                'limit' => $limit,
                'offset' => $offset,
            ],
            'options' => [
                'clients' => $clientOptions,
                'salespersons' => $salespersonOptions,
            ],
        ];
    }
}
