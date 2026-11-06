<?php

declare(strict_types=1);

namespace App\Modules\Report\Services;

use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ExchangeRate\Services\ExchangeRateResolver;
use App\Modules\Pos\Models\PosCashMovement;
use App\Modules\Pos\Models\PosPayment;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use Carbon\Carbon;

class IncomeExpenseReportService
{
    private const PAYMENT_METHOD_LABELS = [
        'cash_usd' => 'Efectivo Divisa ($)',
        'cash_ves' => 'Efectivo Bolívares (Bs)',
        'cash' => 'Efectivo',
        'transfer' => 'Transferencia Bancaria',
        'mobile_payment' => 'Pago Móvil (P2P/C2P)',
        'card_debit' => 'Tarjeta de Débito',
        'card_credit' => 'Tarjeta de Crédito',
        'biopago' => 'Biopago',
        'zelle' => 'Zelle',
        'check' => 'Cheque',
        'other' => 'Otro',
    ];

    public function __construct(
        private readonly ExchangeRateResolver $exchangeRateResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function execute(string $companyId, array $filters): array
    {
        $fromDateStr = (string) ($filters['from_date'] ?? now()->startOfMonth()->toDateString());
        $toDateStr = (string) ($filters['to_date'] ?? now()->toDateString());
        $reportBasis = (string) ($filters['report_basis'] ?? 'cash_flow');
        $paymentMethodFilter = ! empty($filters['payment_method']) ? (string) $filters['payment_method'] : null;

        $fromDate = Carbon::parse($fromDateStr)->startOfDay();
        $toDate = Carbon::parse($toDateStr)->endOfDay();
        $limit = max(1, (int) ($filters['limit'] ?? 15));
        $offset = max(0, (int) ($filters['offset'] ?? 0));

        $rateVes = $this->exchangeRateResolver->tryRateFor($companyId, 'USD', $toDateStr) ?? 1.0;

        $isConsulted = filter_var($filters['consulted'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $paymentMethodOptions = array_map(
            fn ($k, $v) => ['value' => $k, 'label' => $v],
            array_keys(self::PAYMENT_METHOD_LABELS),
            array_values(self::PAYMENT_METHOD_LABELS),
        );

        if (! $isConsulted) {
            return [
                'consulted' => false,
                'report_basis' => $reportBasis,
                'summary' => [
                    'total_inflow_usd' => 0.0,
                    'total_inflow_ves' => 0.0,
                    'total_outflow_usd' => 0.0,
                    'total_outflow_ves' => 0.0,
                    'net_flow_usd' => 0.0,
                    'net_flow_ves' => 0.0,
                    'gross_sales_usd' => 0.0,
                    'credit_notes_usd' => 0.0,
                    'net_sales_usd' => 0.0,
                    'net_sales_ves' => 0.0,
                    'cmv_usd' => 0.0,
                    'cmv_ves' => 0.0,
                    'gross_profit_usd' => 0.0,
                    'gross_profit_ves' => 0.0,
                    'gross_margin_percent' => 0.0,
                    'operating_expenses_usd' => 0.0,
                    'operating_expenses_ves' => 0.0,
                    'operating_result_usd' => 0.0,
                    'operating_result_ves' => 0.0,
                    'operating_margin_percent' => 0.0,
                    'exchange_rate' => $rateVes,
                ],
                'breakdown' => [],
                'rows' => [],
                'meta' => [
                    'total' => 0,
                    'limit' => $limit,
                    'offset' => 0,
                    'has_more' => false,
                ],
                'filters' => [
                    'consulted' => false,
                    'from_date' => $fromDate->toDateString(),
                    'to_date' => $toDate->toDateString(),
                    'report_basis' => $reportBasis,
                    'payment_method' => $paymentMethodFilter ?? 'all',
                    'currency_view' => $filters['currency_view'] ?? 'base',
                    'limit' => $limit,
                    'offset' => 0,
                ],
                'options' => [
                    'payment_methods' => $paymentMethodOptions,
                ],
            ];
        }

        if ($reportBasis === 'cash_flow') {
            return $this->buildCashFlowReport(
                $companyId,
                $fromDate,
                $toDate,
                $rateVes,
                $paymentMethodFilter,
                $limit,
                $offset,
                $filters
            );
        }

        return $this->buildAccrualReport(
            $companyId,
            $fromDate,
            $toDate,
            $rateVes,
            $limit,
            $offset,
            $filters
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function buildCashFlowReport(
        string $companyId,
        Carbon $fromDate,
        Carbon $toDate,
        float $rateVes,
        ?string $paymentMethodFilter,
        int $limit,
        int $offset,
        array $filters
    ): array {
        $movements = [];

        // 1. Cobros a Clientes (ClientCollection)
        $collectionsQuery = ClientCollection::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('collection_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->with(['client:id,name,code']);

        if ($paymentMethodFilter) {
            $collectionsQuery->where('payment_method', $paymentMethodFilter);
        }

        foreach ($collectionsQuery->get() as $col) {
            $amt = (float) $col->amount;
            $curr = strtoupper((string) $col->currency);
            if ($curr === 'VES') {
                $amtVes = $amt;
                $amtUsd = $rateVes > 0 ? $amt / $rateVes : 0.0;
            } else {
                $amtUsd = $amt;
                $docRate = (float) ($col->exchange_rate ?? $rateVes);
                $amtVes = $amt * ($docRate > 0 ? $docRate : $rateVes);
            }

            $movements[] = [
                'id' => $col->id,
                'date' => $col->collection_date?->format('Y-m-d') ?? $col->created_at->format('Y-m-d'),
                'direction' => 'inflow',
                'type_label' => 'Cobro Cliente',
                'concept' => "Cobro {$col->code}",
                'entity_name' => $col->client?->name ?? 'Cliente',
                'reference' => $col->reference ?? $col->code,
                'payment_method' => $col->payment_method,
                'payment_method_label' => self::PAYMENT_METHOD_LABELS[$col->payment_method] ?? $col->payment_method,
                'currency' => $curr,
                'amount_original' => $amt,
                'amount_usd' => round($amtUsd, 2),
                'amount_ves' => round($amtVes, 2),
            ];
        }

        // 2. Cobros en POS (PosPayment)
        $posPaymentsQuery = PosPayment::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->with(['sale:id,code']);

        if ($paymentMethodFilter) {
            $posPaymentsQuery->where('payment_method', $paymentMethodFilter);
        }

        foreach ($posPaymentsQuery->get() as $pos) {
            $amt = (float) $pos->amount_applied;
            $curr = strtoupper((string) $pos->currency);
            if ($curr === 'VES') {
                $amtVes = $amt;
                $amtUsd = $rateVes > 0 ? $amt / $rateVes : 0.0;
            } else {
                $amtUsd = $amt;
                $docRate = (float) ($pos->exchange_rate ?? $rateVes);
                $amtVes = $amt * ($docRate > 0 ? $docRate : $rateVes);
            }

            $movements[] = [
                'id' => $pos->id,
                'date' => $pos->created_at->format('Y-m-d'),
                'direction' => 'inflow',
                'type_label' => 'Venta POS',
                'concept' => 'Venta Mostrador POS',
                'entity_name' => 'Cliente General POS',
                'reference' => $pos->reference ?? ($pos->sale?->code ?? 'POS'),
                'payment_method' => $pos->payment_method,
                'payment_method_label' => self::PAYMENT_METHOD_LABELS[$pos->payment_method] ?? $pos->payment_method,
                'currency' => $curr,
                'amount_original' => $amt,
                'amount_usd' => round($amtUsd, 2),
                'amount_ves' => round($amtVes, 2),
            ];
        }

        // 3. Pagos a Proveedores (SupplierPayment)
        $paymentsQuery = SupplierPayment::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('payment_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->with(['supplier:id,name,code']);

        if ($paymentMethodFilter) {
            $paymentsQuery->where('payment_method', $paymentMethodFilter);
        }

        foreach ($paymentsQuery->get() as $pay) {
            $amt = (float) $pay->amount;
            $curr = strtoupper((string) $pay->currency);
            if ($curr === 'VES') {
                $amtVes = $amt;
                $amtUsd = $rateVes > 0 ? $amt / $rateVes : 0.0;
            } else {
                $amtUsd = $amt;
                $docRate = (float) ($pay->exchange_rate ?? $rateVes);
                $amtVes = $amt * ($docRate > 0 ? $docRate : $rateVes);
            }

            $movements[] = [
                'id' => $pay->id,
                'date' => $pay->payment_date?->format('Y-m-d') ?? $pay->created_at->format('Y-m-d'),
                'direction' => 'outflow',
                'type_label' => 'Pago Proveedor',
                'concept' => "Pago {$pay->code}",
                'entity_name' => $pay->supplier?->name ?? 'Proveedor',
                'reference' => $pay->reference ?? $pay->code,
                'payment_method' => $pay->payment_method,
                'payment_method_label' => self::PAYMENT_METHOD_LABELS[$pay->payment_method] ?? $pay->payment_method,
                'currency' => $curr,
                'amount_original' => $amt,
                'amount_usd' => round($amtUsd, 2),
                'amount_ves' => round($amtVes, 2),
            ];
        }

        // 4. Movimientos de Caja POS (PosCashMovement)
        $cashMovementsQuery = PosCashMovement::query()
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$fromDate, $toDate]);

        foreach ($cashMovementsQuery->get() as $cash) {
            $isOut = $cash->movement_type === 'out';
            $amt = (float) $cash->amount;
            $curr = strtoupper((string) ($cash->currency ?? 'USD'));
            if ($curr === 'VES') {
                $amtVes = $amt;
                $amtUsd = $rateVes > 0 ? $amt / $rateVes : 0.0;
            } else {
                $amtUsd = $amt;
                $amtVes = $amt * $rateVes;
            }

            $conceptLabel = $cash->concept === 'expense'
                ? 'Gasto de Caja Chica'
                : ($cash->concept === 'cash_drop' ? 'Retiro / Sangría' : 'Movimiento de Caja');

            $movements[] = [
                'id' => $cash->id,
                'date' => $cash->created_at->format('Y-m-d'),
                'direction' => $isOut ? 'outflow' : 'inflow',
                'type_label' => $isOut ? 'Gasto Caja' : 'Ingreso Caja',
                'concept' => $conceptLabel.($cash->reason ? " ({$cash->reason})" : ''),
                'entity_name' => 'Caja POS',
                'reference' => 'Caja POS',
                'payment_method' => 'cash',
                'payment_method_label' => 'Efectivo',
                'currency' => $curr,
                'amount_original' => $amt,
                'amount_usd' => round($amtUsd, 2),
                'amount_ves' => round($amtVes, 2),
            ];
        }

        // Ordenar por fecha descendente
        usort($movements, fn ($a, $b) => strcmp($b['date'], $a['date']));

        $totalInflowUsd = 0.0;
        $totalInflowVes = 0.0;
        $totalOutflowUsd = 0.0;
        $totalOutflowVes = 0.0;

        $breakdownByMethod = [];

        foreach ($movements as $m) {
            $method = $m['payment_method'] ?? 'other';
            if (! isset($breakdownByMethod[$method])) {
                $breakdownByMethod[$method] = [
                    'method' => $method,
                    'label' => self::PAYMENT_METHOD_LABELS[$method] ?? ucfirst($method),
                    'inflow_usd' => 0.0,
                    'outflow_usd' => 0.0,
                    'inflow_ves' => 0.0,
                    'outflow_ves' => 0.0,
                    'net_usd' => 0.0,
                    'net_ves' => 0.0,
                ];
            }

            if ($m['direction'] === 'inflow') {
                $totalInflowUsd += $m['amount_usd'];
                $totalInflowVes += $m['amount_ves'];
                $breakdownByMethod[$method]['inflow_usd'] += $m['amount_usd'];
                $breakdownByMethod[$method]['inflow_ves'] += $m['amount_ves'];
            } else {
                $totalOutflowUsd += $m['amount_usd'];
                $totalOutflowVes += $m['amount_ves'];
                $breakdownByMethod[$method]['outflow_usd'] += $m['amount_usd'];
                $breakdownByMethod[$method]['outflow_ves'] += $m['amount_ves'];
            }
        }

        foreach ($breakdownByMethod as &$b) {
            $b['net_usd'] = round($b['inflow_usd'] - $b['outflow_usd'], 2);
            $b['net_ves'] = round($b['inflow_ves'] - $b['outflow_ves'], 2);
            $b['inflow_usd'] = round($b['inflow_usd'], 2);
            $b['outflow_usd'] = round($b['outflow_usd'], 2);
            $b['inflow_ves'] = round($b['inflow_ves'], 2);
            $b['outflow_ves'] = round($b['outflow_ves'], 2);
        }
        unset($b);

        $netUsd = $totalInflowUsd - $totalOutflowUsd;
        $netVes = $totalInflowVes - $totalOutflowVes;

        $totalMovements = count($movements);
        $pageMovements = array_slice($movements, $offset, $limit);

        return [
            'consulted' => true,
            'report_basis' => 'cash_flow',
            'summary' => [
                'total_inflow_usd' => round($totalInflowUsd, 2),
                'total_inflow_ves' => round($totalInflowVes, 2),
                'total_outflow_usd' => round($totalOutflowUsd, 2),
                'total_outflow_ves' => round($totalOutflowVes, 2),
                'net_flow_usd' => round($netUsd, 2),
                'net_flow_ves' => round($netVes, 2),
                'exchange_rate' => $rateVes,
            ],
            'breakdown' => array_values($breakdownByMethod),
            'rows' => $pageMovements,
            'meta' => [
                'total' => $totalMovements,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => $totalMovements > $offset + $limit,
            ],
            'filters' => [
                'consulted' => true,
                'from_date' => $fromDate->toDateString(),
                'to_date' => $toDate->toDateString(),
                'report_basis' => 'cash_flow',
                'payment_method' => $paymentMethodFilter ?? 'all',
                'currency_view' => $filters['currency_view'] ?? 'base',
                'limit' => $limit,
                'offset' => $offset,
            ],
            'options' => [
                'payment_methods' => array_map(
                    fn ($k, $v) => ['value' => $k, 'label' => $v],
                    array_keys(self::PAYMENT_METHOD_LABELS),
                    array_values(self::PAYMENT_METHOD_LABELS),
                ),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function buildAccrualReport(
        string $companyId,
        Carbon $fromDate,
        Carbon $toDate,
        float $rateVes,
        int $limit,
        int $offset,
        array $filters
    ): array {
        $documents = [];

        // 1. Facturas de venta emitidas
        $salesQuery = SalesInvoice::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('invoice_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->with(['client:id,name']);

        $grossSales = 0.0;
        $totalCmv = 0.0;

        foreach ($salesQuery->get() as $inv) {
            $sub = (float) $inv->subtotal;
            $cost = (float) ($inv->total_cost ?? 0);
            $curr = strtoupper((string) $inv->currency);

            if ($curr === 'VES') {
                $subUsd = $rateVes > 0 ? $sub / $rateVes : 0.0;
                $costUsd = $rateVes > 0 ? $cost / $rateVes : 0.0;
            } else {
                $subUsd = $sub;
                $costUsd = $cost;
            }

            $grossSales += $subUsd;
            $totalCmv += $costUsd;

            $documents[] = [
                'id' => $inv->id,
                'date' => $inv->invoice_date?->format('Y-m-d'),
                'type' => 'sale',
                'type_label' => 'Factura de Venta',
                'code' => $inv->code,
                'entity_name' => $inv->client?->name ?? 'Cliente',
                'currency' => $curr,
                'amount_original' => (float) $inv->total,
                'subtotal_usd' => round($subUsd, 2),
                'subtotal_ves' => round($subUsd * $rateVes, 2),
                'cost_usd' => round($costUsd, 2),
                'cost_ves' => round($costUsd * $rateVes, 2),
            ];
        }

        // 2. Notas de crédito de clientes
        $creditNotesQuery = SalesCreditNote::query()
            ->where('company_id', $companyId)
            ->where('status', 'confirmed')
            ->whereBetween('note_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->with(['client:id,name']);

        $totalCreditNotes = 0.0;

        foreach ($creditNotesQuery->get() as $cn) {
            $tot = (float) $cn->total;
            $curr = strtoupper((string) $cn->currency);
            $totUsd = $curr === 'VES' ? ($rateVes > 0 ? $tot / $rateVes : 0.0) : $tot;
            $totalCreditNotes += $totUsd;

            $documents[] = [
                'id' => $cn->id,
                'date' => $cn->note_date?->format('Y-m-d') ?? $cn->created_at->format('Y-m-d'),
                'type' => 'credit_note',
                'type_label' => 'Nota de Crédito',
                'code' => $cn->code,
                'entity_name' => $cn->client?->name ?? 'Cliente',
                'currency' => $curr,
                'amount_original' => $tot,
                'subtotal_usd' => round(-$totUsd, 2),
                'subtotal_ves' => round(-$totUsd * $rateVes, 2),
                'cost_usd' => 0.0,
                'cost_ves' => 0.0,
            ];
        }

        $netSales = max(0.0, $grossSales - $totalCreditNotes);
        $grossProfit = $netSales - $totalCmv;
        $grossMarginPercent = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 1) : 0.0;

        // 3. Gastos operativos: facturas de compras
        $purchasesQuery = PurchaseInvoice::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('invoice_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->with(['supplier:id,name']);

        $totalPurchases = 0.0;

        foreach ($purchasesQuery->get() as $pInv) {
            $sub = (float) $pInv->subtotal;
            $curr = strtoupper((string) $pInv->currency);
            $subUsd = $curr === 'VES' ? ($rateVes > 0 ? $sub / $rateVes : 0.0) : $sub;
            $totalPurchases += $subUsd;

            $documents[] = [
                'id' => $pInv->id,
                'date' => $pInv->invoice_date?->format('Y-m-d'),
                'type' => 'purchase',
                'type_label' => 'Factura de Compra / Gasto',
                'code' => $pInv->code,
                'entity_name' => $pInv->supplier?->name ?? 'Proveedor',
                'currency' => $curr,
                'amount_original' => (float) $pInv->total,
                'subtotal_usd' => round($subUsd, 2),
                'subtotal_ves' => round($subUsd * $rateVes, 2),
                'cost_usd' => 0.0,
                'cost_ves' => 0.0,
            ];
        }

        // 4. Gastos operativos menores de POS
        $posExpenses = (float) PosCashMovement::query()
            ->where('company_id', $companyId)
            ->where('movement_type', 'out')
            ->where('concept', 'expense')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->sum('amount');

        $totalExpenses = $totalPurchases + $posExpenses;
        $operatingResult = $grossProfit - $totalExpenses;
        $operatingMarginPercent = $netSales > 0 ? round(($operatingResult / $netSales) * 100, 1) : 0.0;

        usort($documents, fn ($a, $b) => strcmp($b['date'], $a['date']));

        $totalDocs = count($documents);
        $pageDocs = array_slice($documents, $offset, $limit);

        return [
            'consulted' => true,
            'report_basis' => 'accrual',
            'summary' => [
                'gross_sales_usd' => round($grossSales, 2),
                'credit_notes_usd' => round($totalCreditNotes, 2),
                'net_sales_usd' => round($netSales, 2),
                'net_sales_ves' => round($netSales * $rateVes, 2),
                'cmv_usd' => round($totalCmv, 2),
                'cmv_ves' => round($totalCmv * $rateVes, 2),
                'gross_profit_usd' => round($grossProfit, 2),
                'gross_profit_ves' => round($grossProfit * $rateVes, 2),
                'gross_margin_percent' => $grossMarginPercent,
                'operating_expenses_usd' => round($totalExpenses, 2),
                'operating_expenses_ves' => round($totalExpenses * $rateVes, 2),
                'operating_result_usd' => round($operatingResult, 2),
                'operating_result_ves' => round($operatingResult * $rateVes, 2),
                'operating_margin_percent' => $operatingMarginPercent,
                'exchange_rate' => $rateVes,
            ],
            'rows' => $pageDocs,
            'meta' => [
                'total' => $totalDocs,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => $totalDocs > $offset + $limit,
            ],
            'filters' => [
                'consulted' => true,
                'from_date' => $fromDate->toDateString(),
                'to_date' => $toDate->toDateString(),
                'report_basis' => 'accrual',
                'currency_view' => $filters['currency_view'] ?? 'base',
                'limit' => $limit,
                'offset' => $offset,
            ],
            'options' => [
                'payment_methods' => array_map(
                    fn ($k, $v) => ['value' => $k, 'label' => $v],
                    array_keys(self::PAYMENT_METHOD_LABELS),
                    array_values(self::PAYMENT_METHOD_LABELS),
                ),
            ],
        ];
    }
}
