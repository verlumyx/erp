<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\Configuration\Services\ConfigurationFindService;
use App\Modules\Item\Models\Item;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesOrder\Models\SalesOrder;
use Carbon\Carbon;

class DashboardStatsService
{
    public function __construct(
        private readonly ConfigurationFindService $configurationService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(string $companyId): array
    {
        $today = Carbon::today()->toDateString();
        $configuration = $this->configurationService->execute($companyId);
        $baseCurrency = strtoupper($configuration->base_currency);

        $sales = SalesInvoice::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereDate('invoice_date', $today)
            ->get(['total', 'total_ves', 'currency', 'exchange_rate', 'base_currency', 'base_exchange_rate']);

        $todaySalesBase = 0.0;
        $todaySalesVes = 0.0;
        foreach ($sales as $sale) {
            $converted = $this->convert(
                (float) $sale->total,
                (string) $sale->currency,
                $baseCurrency,
                (float) $sale->exchange_rate,
                (float) ($sale->base_exchange_rate ?? $sale->exchange_rate),
            );
            $todaySalesBase += $converted['base'];
            $todaySalesVes += ((float) $sale->total_ves > 0 ? (float) $sale->total_ves : $converted['ves']);
        }

        $collections = ClientCollection::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereDate('collection_date', $today)
            ->get(['amount', 'currency', 'exchange_rate', 'base_currency', 'base_exchange_rate']);

        $todayCollectionsBase = 0.0;
        $todayCollectionsVes = 0.0;
        foreach ($collections as $collection) {
            $converted = $this->convert(
                (float) $collection->amount,
                (string) $collection->currency,
                $baseCurrency,
                (float) $collection->exchange_rate,
                (float) ($collection->base_exchange_rate ?? $collection->exchange_rate),
            );
            $todayCollectionsBase += $converted['base'];
            $todayCollectionsVes += $converted['ves'];
        }

        $unpaidSales = SalesInvoice::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->where('balance', '>', 0)
            ->get(['balance', 'currency', 'exchange_rate', 'base_currency', 'base_exchange_rate']);

        $receivablesBalance = 0.0;
        foreach ($unpaidSales as $inv) {
            $converted = $this->convert(
                (float) $inv->balance,
                (string) $inv->currency,
                $baseCurrency,
                (float) $inv->exchange_rate,
                (float) ($inv->base_exchange_rate ?? $inv->exchange_rate),
            );
            $receivablesBalance += $converted['base'];
        }

        $unpaidPurchases = PurchaseInvoice::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->where('balance', '>', 0)
            ->get(['balance', 'currency', 'exchange_rate', 'base_currency', 'base_exchange_rate']);

        $payablesBalance = 0.0;
        foreach ($unpaidPurchases as $inv) {
            $converted = $this->convert(
                (float) $inv->balance,
                (string) $inv->currency,
                $baseCurrency,
                (float) $inv->exchange_rate,
                (float) ($inv->base_exchange_rate ?? $inv->exchange_rate),
            );
            $payablesBalance += $converted['base'];
        }

        $overdueSales = SalesInvoice::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->where('balance', '>', 0)
            ->whereDate('due_date', '<', $today)
            ->get(['balance', 'currency', 'exchange_rate', 'base_currency', 'base_exchange_rate']);

        $overdueBalance = 0.0;
        foreach ($overdueSales as $inv) {
            $converted = $this->convert(
                (float) $inv->balance,
                (string) $inv->currency,
                $baseCurrency,
                (float) $inv->exchange_rate,
                (float) ($inv->base_exchange_rate ?? $inv->exchange_rate),
            );
            $overdueBalance += $converted['base'];
        }

        $lowStockCount = Item::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereIn('type', ['inventoried', 'serialized'])
            ->where('min_stock', '>', 0)
            ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM app_item_stocks WHERE app_item_stocks.item_id = app_items.id AND app_item_stocks.company_id = app_items.company_id) <= app_items.min_stock')
            ->count();

        $pendingOrdersCount = SalesOrder::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['confirmed', 'partial'])
            ->count();

        $recentInvoices = SalesInvoice::query()
            ->with('client:id,name')
            ->where('company_id', $companyId)
            ->latest('invoice_date')
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'code', 'client_id', 'total', 'currency', 'status', 'payment_status', 'invoice_date'])
            ->map(fn (SalesInvoice $inv): array => [
                'id' => $inv->id,
                'code' => $inv->code,
                'clientName' => $inv->client?->name ?? 'Cliente General',
                'total' => (float) $inv->total,
                'currency' => $inv->currency,
                'status' => $inv->status,
                'paymentStatus' => $inv->payment_status,
                'invoiceDate' => (string) $inv->invoice_date,
            ])
            ->all();

        return [
            'kpis' => [
                'todaySales' => [
                    'total' => round($todaySalesBase, 2),
                    'totalVes' => round($todaySalesVes, 2),
                    'count' => $sales->count(),
                ],
                'todayCollections' => [
                    'total' => round($todayCollectionsBase, 2),
                    'totalVes' => round($todayCollectionsVes, 2),
                    'count' => $collections->count(),
                ],
                'receivables' => [
                    'balance' => round($receivablesBalance, 2),
                    'count' => $unpaidSales->count(),
                ],
                'payables' => [
                    'balance' => round($payablesBalance, 2),
                    'count' => $unpaidPurchases->count(),
                ],
            ],
            'alerts' => [
                'overdueInvoicesCount' => $overdueSales->count(),
                'overdueInvoicesBalance' => round($overdueBalance, 2),
                'lowStockItemsCount' => $lowStockCount,
                'pendingOrdersCount' => $pendingOrdersCount,
            ],
            'recentInvoices' => $recentInvoices,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function emptyStats(): array
    {
        return [
            'kpis' => [
                'todaySales' => ['total' => 0.0, 'totalVes' => 0.0, 'count' => 0],
                'todayCollections' => ['total' => 0.0, 'totalVes' => 0.0, 'count' => 0],
                'receivables' => ['balance' => 0.0, 'count' => 0],
                'payables' => ['balance' => 0.0, 'count' => 0],
            ],
            'alerts' => [
                'overdueInvoicesCount' => 0,
                'overdueInvoicesBalance' => 0.0,
                'lowStockItemsCount' => 0,
                'pendingOrdersCount' => 0,
            ],
            'recentInvoices' => [],
        ];
    }

    /**
     * Convierte cualquier monto de un documento a la moneda base de la empresa y a bolívares (VES).
     *
     * @return array{base: float, ves: float}
     */
    private function convert(float $amount, string $currency, string $baseCurrency, float $exchangeRate, float $baseExchangeRate): array
    {
        $currency = strtoupper($currency);
        $baseCurrency = strtoupper($baseCurrency);

        if ($currency === $baseCurrency) {
            $inBase = $amount;
            $inVes = $baseCurrency === 'VES'
                ? $amount
                : ($exchangeRate > 0 ? round($amount * $exchangeRate, 2) : $amount);
        } elseif ($currency === 'VES' && $baseCurrency === 'USD') {
            $inBase = $exchangeRate > 0 ? round($amount / $exchangeRate, 2) : $amount;
            $inVes = $amount;
        } elseif ($currency === 'USD' && $baseCurrency === 'VES') {
            $inBase = $exchangeRate > 0 ? round($amount * $exchangeRate, 2) : $amount;
            $inVes = $inBase;
        } else {
            $inVes = $exchangeRate > 0 ? round($amount * $exchangeRate, 2) : $amount;
            $inBase = $baseExchangeRate > 0 ? round($inVes / $baseExchangeRate, 2) : $amount;
        }

        return ['base' => $inBase, 'ves' => $inVes];
    }
}
