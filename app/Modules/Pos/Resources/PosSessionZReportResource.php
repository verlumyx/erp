<?php

declare(strict_types=1);

namespace App\Modules\Pos\Resources;

use App\Modules\Pos\Models\PosSession;
use App\Modules\Pos\Services\PosSessionCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosSessionZReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PosSession $session */
        $session = $this->resource;
        $calcService = app(PosSessionCalculationService::class);
        $calc = $calcService->calculate($session);

        $gross = round($calc['totalSalesAmount'] + $calc['totalDiscountsAmount'], 2);
        $taxableBase = round($calc['totalSalesAmount'] - $calc['totalTaxAmount'], 2);

        $diffUsd = $session->declared_cash_usd !== null
            ? round((float) $session->declared_cash_usd - $calc['cashUsd'], 2)
            : null;

        $diffVes = $session->declared_cash_ves !== null
            ? round((float) $session->declared_cash_ves - $calc['cashVes'], 2)
            : null;

        return [
            'type' => $session->isClosed() ? 'Z' : 'X',
            'session' => [
                'id' => $session->id,
                'code' => $session->code,
                'register_name' => $session->register?->name,
                'warehouse_name' => $session->register?->warehouse?->name,
                'cashier_name' => $session->user?->name,
                'cashier_email' => $session->user?->email,
                'opened_at' => $session->opened_at?->format('Y-m-d H:i:s'),
                'closed_at' => $session->closed_at?->format('Y-m-d H:i:s'),
                'status' => $session->status,
                'closing_notes' => $session->closing_notes,
                'supervisor_name' => $session->supervisor?->name,
            ],
            'company' => [
                'id' => $session->company?->id,
                'name' => $session->company?->name,
                'tax_number' => $session->company?->tax_number ?? $session->company?->rif ?? '',
                'address' => $session->company?->address ?? '',
            ],
            'summary' => [
                'sales_count' => $calc['salesCount'],
                'cancelled_sales_count' => $calc['cancelledCount'],
                'average_ticket' => $calc['averageTicket'],
                'total_gross' => $gross,
                'discounts' => $calc['totalDiscountsAmount'],
                'taxable_base' => $taxableBase,
                'total_tax' => $calc['totalTaxAmount'],
                'total_net' => $calc['totalSalesAmount'],
            ],
            'payments' => [
                'cash_usd' => $calc['salesCashUsd'],
                'cash_ves' => $calc['salesCashVes'],
                'card_ves' => $calc['salesCardVes'],
                'mobile_ves' => $calc['salesMobileVes'],
                'biopago_ves' => $calc['salesBiopagoVes'],
                'transfer_usd' => $calc['salesTransferUsd'],
                'transfer_ves' => $calc['salesTransferVes'],
                'other' => $calc['salesOther'],
            ],
            'audit_usd' => [
                'opening_cash' => (float) $session->opening_cash_usd,
                'sales_cash' => $calc['salesCashUsd'],
                'cash_in' => $calc['cashInUsd'],
                'cash_out' => $calc['cashOutUsd'],
                'expected' => $calc['cashUsd'],
                'declared' => $session->declared_cash_usd !== null ? (float) $session->declared_cash_usd : null,
                'difference' => $diffUsd,
                'status' => $diffUsd === null ? 'pending' : ($diffUsd == 0 ? 'exact' : ($diffUsd > 0 ? 'surplus' : 'deficit')),
            ],
            'audit_ves' => [
                'opening_cash' => (float) $session->opening_cash_ves,
                'sales_cash' => $calc['salesCashVes'],
                'cash_in' => $calc['cashInVes'],
                'cash_out' => $calc['cashOutVes'],
                'expected' => $calc['cashVes'],
                'declared' => $session->declared_cash_ves !== null ? (float) $session->declared_cash_ves : null,
                'difference' => $diffVes,
                'status' => $diffVes === null ? 'pending' : ($diffVes == 0 ? 'exact' : ($diffVes > 0 ? 'surplus' : 'deficit')),
            ],
            'denominations' => $session->denominations->map(fn ($d) => [
                'currency' => $d->currency,
                'denomination' => (float) $d->denomination,
                'count' => (int) $d->count,
                'total_amount' => (float) $d->total_amount,
            ]),
            'cash_movements' => $session->cashMovements->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->movement_type,
                'concept' => $m->concept,
                'currency' => $m->currency,
                'amount' => (float) $m->amount,
                'reason' => $m->reason,
                'authorized_by' => $m->authorizer?->name,
                'created_at' => $m->created_at?->format('Y-m-d H:i:s'),
            ]),
        ];
    }
}
