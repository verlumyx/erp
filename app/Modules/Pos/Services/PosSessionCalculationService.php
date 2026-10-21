<?php

declare(strict_types=1);

namespace App\Modules\Pos\Services;

use App\Modules\Pos\Models\PosCashMovement;
use App\Modules\Pos\Models\PosPayment;
use App\Modules\Pos\Models\PosSale;
use App\Modules\Pos\Models\PosSession;

class PosSessionCalculationService
{
    /**
     * @return array{
     *     cashUsd: float,
     *     cashVes: float,
     *     cardVes: float,
     *     mobileVes: float,
     *     biopagoVes: float,
     *     transferUsd: float,
     *     transferVes: float,
     *     other: float,
     *     salesCashUsd: float,
     *     salesCashVes: float,
     *     salesCardVes: float,
     *     salesMobileVes: float,
     *     salesBiopagoVes: float,
     *     salesTransferUsd: float,
     *     salesTransferVes: float,
     *     salesOther: float,
     *     cashInUsd: float,
     *     cashOutUsd: float,
     *     cashInVes: float,
     *     cashOutVes: float,
     *     salesCount: int,
     *     cancelledCount: int,
     *     totalSalesAmount: float,
     *     totalDiscountsAmount: float,
     *     totalTaxAmount: float,
     *     averageTicket: float
     * }
     */
    public function calculate(PosSession $session): array
    {
        // 1. Completed sales in this session
        $completedSales = PosSale::query()
            ->where('pos_session_id', $session->id)
            ->where('status', PosSale::STATUS_COMPLETED)
            ->get();

        $cancelledSalesCount = PosSale::query()
            ->where('pos_session_id', $session->id)
            ->where('status', PosSale::STATUS_CANCELLED)
            ->count();

        $salesCount = $completedSales->count();
        $totalSalesAmount = round((float) $completedSales->sum('total'), 2);
        $totalDiscountsAmount = round((float) $completedSales->sum('discount_amount'), 2);
        $totalTaxAmount = round((float) $completedSales->sum('tax_amount'), 2);
        $averageTicket = $salesCount > 0 ? round($totalSalesAmount / $salesCount, 2) : 0.0;

        // Total changes given
        $totalChangeUsd = round((float) $completedSales->sum('change_amount_usd'), 2);
        $totalChangeVes = round((float) $completedSales->sum('change_amount_ves'), 2);

        // 2. Payments grouped by method and currency on completed sales
        $completedSaleIds = $completedSales->pluck('id')->all();

        $payments = PosPayment::query()
            ->whereIn('pos_sale_id', $completedSaleIds)
            ->where('status', 'active')
            ->get();

        // Cash payments
        $cashPaymentsUsd = $payments->where('payment_method', 'cash')->where('currency', 'USD');
        $cashPaymentsVes = $payments->where('payment_method', 'cash')->where('currency', 'VES');

        // We use amount_tendered if present, fallback to amount_applied
        $tenderedUsd = (float) $cashPaymentsUsd->sum(fn ($p) => (float) ($p->amount_tendered ?: $p->amount_applied));
        $tenderedVes = (float) $cashPaymentsVes->sum(fn ($p) => (float) ($p->amount_tendered ?: $p->amount_applied));

        $salesCashUsd = round($tenderedUsd - $totalChangeUsd, 2);
        $salesCashVes = round($tenderedVes - $totalChangeVes, 2);

        // Electronic payments
        $cardPayments = $payments->whereIn('payment_method', ['card_debit', 'card_credit']);
        $salesCardVes = round((float) $cardPayments->sum('amount_applied'), 2);

        $mobilePayments = $payments->where('payment_method', 'mobile_payment');
        $salesMobileVes = round((float) $mobilePayments->sum('amount_applied'), 2);

        $biopagoPayments = $payments->where('payment_method', 'biopago');
        $salesBiopagoVes = round((float) $biopagoPayments->sum('amount_applied'), 2);

        $transferPayments = $payments->where('payment_method', 'transfer');
        $salesTransferVes = round((float) $transferPayments->where('currency', 'VES')->sum('amount_applied'), 2);
        $salesTransferUsd = round((float) $transferPayments->where('currency', '!=', 'VES')->sum('amount_applied'), 2);

        $otherPayments = $payments->whereIn('payment_method', ['credit_note', 'other']);
        $salesOther = round((float) $otherPayments->sum('amount_applied'), 2);

        // 3. Manual cash movements
        $cashMovements = PosCashMovement::query()
            ->where('pos_session_id', $session->id)
            ->where('status', 'active')
            ->get();

        $cashInUsd = round((float) $cashMovements->where('movement_type', 'in')->where('currency', 'USD')->sum('amount'), 2);
        $cashOutUsd = round((float) $cashMovements->where('movement_type', 'out')->where('currency', 'USD')->sum('amount'), 2);

        $cashInVes = round((float) $cashMovements->where('movement_type', 'in')->where('currency', 'VES')->sum('amount'), 2);
        $cashOutVes = round((float) $cashMovements->where('movement_type', 'out')->where('currency', 'VES')->sum('amount'), 2);

        // 4. Expected Totals
        $openingUsd = (float) $session->opening_cash_usd;
        $openingVes = (float) $session->opening_cash_ves;

        $expectedCashUsd = round($openingUsd + $salesCashUsd + $cashInUsd - $cashOutUsd, 2);
        $expectedCashVes = round($openingVes + $salesCashVes + $cashInVes - $cashOutVes, 2);
        $expectedCardVes = $salesCardVes;
        $expectedMobileVes = $salesMobileVes;
        $expectedBiopagoVes = $salesBiopagoVes;
        $expectedTransferUsd = $salesTransferUsd;
        $expectedTransferVes = $salesTransferVes;
        $expectedOther = $salesOther;

        return [
            'cashUsd' => $expectedCashUsd,
            'cashVes' => $expectedCashVes,
            'cardVes' => $expectedCardVes,
            'mobileVes' => $expectedMobileVes,
            'biopagoVes' => $expectedBiopagoVes,
            'transferUsd' => $expectedTransferUsd,
            'transferVes' => $expectedTransferVes,
            'other' => $expectedOther,
            'salesCashUsd' => $salesCashUsd,
            'salesCashVes' => $salesCashVes,
            'salesCardVes' => $salesCardVes,
            'salesMobileVes' => $salesMobileVes,
            'salesBiopagoVes' => $salesBiopagoVes,
            'salesTransferUsd' => $salesTransferUsd,
            'salesTransferVes' => $salesTransferVes,
            'salesOther' => $salesOther,
            'cashInUsd' => $cashInUsd,
            'cashOutUsd' => $cashOutUsd,
            'cashInVes' => $cashInVes,
            'cashOutVes' => $cashOutVes,
            'salesCount' => $salesCount,
            'cancelledCount' => $cancelledSalesCount,
            'totalSalesAmount' => $totalSalesAmount,
            'totalDiscountsAmount' => $totalDiscountsAmount,
            'totalTaxAmount' => $totalTaxAmount,
            'averageTicket' => $averageTicket,
        ];
    }
}
