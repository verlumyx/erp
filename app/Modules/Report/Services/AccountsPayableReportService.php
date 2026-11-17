<?php

declare(strict_types=1);

namespace App\Modules\Report\Services;

use App\Modules\ExchangeRate\Services\ExchangeRateResolver;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use App\Modules\SupplierType\Models\SupplierType;
use Carbon\Carbon;

class AccountsPayableReportService
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
        $supplierId = ! empty($filters['supplier_id']) ? (string) $filters['supplier_id'] : null;
        $supplierTypeId = ! empty($filters['supplier_type_id']) ? (string) $filters['supplier_type_id'] : null;
        $paymentStatus = ! empty($filters['payment_status']) && $filters['payment_status'] !== 'all'
            ? (string) $filters['payment_status']
            : null;

        // Base purchase invoices query: collectible invoices with pending balance as of cutoff date
        $baseInvoicesQuery = PurchaseInvoice::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->where('balance', '>', 0)
            ->whereDate('invoice_date', '<=', $asOfDate);

        if ($supplierId) {
            $baseInvoicesQuery->where('supplier_id', $supplierId);
        }

        if ($supplierTypeId) {
            $baseInvoicesQuery->whereHas('supplier', fn ($q) => $q->where('supplier_type_id', $supplierTypeId));
        }

        if ($paymentStatus === 'overdue') {
            $baseInvoicesQuery->whereDate('due_date', '<', $asOfDate);
        } elseif ($paymentStatus === 'pending') {
            $baseInvoicesQuery->where('paid_amount', '=', 0);
        } elseif ($paymentStatus === 'partial') {
            $baseInvoicesQuery->where('paid_amount', '>', 0);
        }

        $rateVes = $this->exchangeRateResolver->tryRateFor($companyId, 'USD', $asOfDateStr) ?? 1.0;

        $isConsulted = filter_var($filters['consulted'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Opciones para selectores de filtro
        $supplierOptions = Supplier::query()
            ->where('company_id', $companyId)
            ->select('id', 'name', 'code', 'document_type', 'document_number')
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'value' => $s->id,
                'label' => "{$s->name} ({$s->document_type}-{$s->document_number})",
            ])
            ->all();

        $supplierTypeOptions = SupplierType::query()
            ->where('company_id', $companyId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn ($st) => [
                'value' => $st->id,
                'label' => $st->name,
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
                    'total_suppliers' => 0,
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
                    'supplier_id' => $supplierId,
                    'supplier_type_id' => $supplierTypeId,
                    'payment_status' => $paymentStatus ?? 'all',
                    'currency_view' => $filters['currency_view'] ?? 'base',
                    'limit' => $limit,
                    'offset' => 0,
                ],
                'options' => [
                    'suppliers' => $supplierOptions,
                    'supplier_types' => $supplierTypeOptions,
                ],
            ];
        }

        // 1. Obtener lista paginada de proveedores con facturas pendientes
        $supplierIdsQuery = (clone $baseInvoicesQuery)->select('supplier_id')->distinct();
        $totalSuppliers = $supplierIdsQuery->count('supplier_id');
        $pageSupplierIds = (clone $supplierIdsQuery)
            ->offset($offset)
            ->limit($limit)
            ->pluck('supplier_id')
            ->all();

        $suppliers = Supplier::query()
            ->whereIn('id', $pageSupplierIds)
            ->with(['supplierType:id,name'])
            ->get()
            ->keyBy('id');

        $rows = [];
        foreach ($pageSupplierIds as $sId) {
            $supplier = $suppliers->get($sId);
            if (! $supplier) {
                continue;
            }

            // Facturas de este proveedor
            $invoices = (clone $baseInvoicesQuery)
                ->where('supplier_id', $sId)
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
                $daysOverdue = (int) $dueDate->diffInDays($asOfDate, false);
                $isOverdue = $daysOverdue > 0;

                $bal = (float) $inv->balance;
                $grossDebt += $bal;

                // Contravalores en USD y VES
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

                // Tramos de antigüedad (Aging)
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
                    'supplier_invoice_series' => $inv->supplier_invoice_series,
                    'supplier_invoice_number' => $inv->supplier_invoice_number,
                    'full_number' => $inv->supplier_invoice_series
                        ? "{$inv->supplier_invoice_series}-{$inv->supplier_invoice_number}"
                        : ($inv->supplier_invoice_number ?? $inv->code),
                    'invoice_date' => $inv->invoice_date?->format('Y-m-d'),
                    'received_date' => $inv->received_date?->format('Y-m-d'),
                    'due_date' => $inv->due_date?->format('Y-m-d'),
                    'days_overdue' => $daysOverdue,
                    'is_overdue' => $isOverdue,
                    'currency' => $inv->currency,
                    'total' => (float) $inv->total,
                    'withholding_amount' => (float) ($inv->withholding_amount ?? 0),
                    'paid_amount' => (float) $inv->paid_amount,
                    'balance' => $bal,
                    'balance_usd' => round($balUsd, 2),
                    'balance_ves' => round($balVes, 2),
                    'payment_status' => $invStatus,
                ];
            }

            // Deducciones: anticipos entregados y notas de crédito a favor de la empresa
            $advancesBalance = (float) SupplierAdvance::query()
                ->where('company_id', $companyId)
                ->where('supplier_id', $sId)
                ->where('status', 'confirmed')
                ->where('balance', '>', 0)
                ->sum('balance');

            $creditNotesBalance = (float) PurchaseCreditNote::query()
                ->where('company_id', $companyId)
                ->where('supplier_id', $sId)
                ->where('status', 'confirmed')
                ->where('balance', '>', 0)
                ->sum('balance');

            $netDebt = max(0.0, $grossDebt - $advancesBalance - $creditNotesBalance);

            $rows[] = [
                'supplier' => [
                    'id' => $supplier->id,
                    'code' => $supplier->code,
                    'name' => $supplier->name,
                    'legal_name' => $supplier->legal_name,
                    'tax_number' => "{$supplier->document_type}-{$supplier->document_number}",
                    'document_type' => $supplier->document_type,
                    'document_number' => $supplier->document_number,
                    'supplier_type_name' => $supplier->supplierType?->name ?? 'General',
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

        // 2. Resumen ejecutivo global de pasivos con proveedores
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

        $allSupplierIdsWithDebt = (clone $baseInvoicesQuery)->select('supplier_id')->distinct()->pluck('supplier_id');
        $totalAdvances = (float) SupplierAdvance::query()
            ->where('company_id', $companyId)
            ->whereIn('supplier_id', $allSupplierIdsWithDebt)
            ->where('status', 'confirmed')
            ->where('balance', '>', 0)
            ->sum('balance');

        $totalCreditNotes = (float) PurchaseCreditNote::query()
            ->where('company_id', $companyId)
            ->whereIn('supplier_id', $allSupplierIdsWithDebt)
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
            'total_suppliers' => $totalSuppliers,
            'total_invoices' => $allInvoices->count(),
            'exchange_rate' => $rateVes,
        ];

        return [
            'consulted' => true,
            'rows' => $rows,
            'summary' => $summary,
            'meta' => [
                'total' => $totalSuppliers,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => $totalSuppliers > $offset + $limit,
            ],
            'filters' => [
                'consulted' => true,
                'as_of_date' => $asOfDateStr,
                'supplier_id' => $supplierId,
                'supplier_type_id' => $supplierTypeId,
                'payment_status' => $paymentStatus ?? 'all',
                'currency_view' => $filters['currency_view'] ?? 'base',
                'limit' => $limit,
                'offset' => $offset,
            ],
            'options' => [
                'suppliers' => $supplierOptions,
                'supplier_types' => $supplierTypeOptions,
            ],
        ];
    }
}
