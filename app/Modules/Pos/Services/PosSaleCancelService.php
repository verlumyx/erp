<?php

declare(strict_types=1);

namespace App\Modules\Pos\Services;

use App\Modules\Dispatch\Commands\UpdateStatusDispatchCommand;
use App\Modules\Dispatch\Services\DispatchUpdateStatusService;
use App\Modules\Pos\Models\PosSale;
use App\Modules\Pos\Repositories\Contracts\PosSaleRepositoryInterface;
use App\Modules\SalesInvoice\Commands\UpdateStatusSalesInvoiceCommand;
use App\Modules\SalesInvoice\Services\SalesInvoiceUpdateStatusService;
use Illuminate\Support\Facades\DB;

class PosSaleCancelService
{
    public function __construct(
        private readonly PosSaleRepositoryInterface $saleRepository,
        private readonly SalesInvoiceUpdateStatusService $invoiceStatusService,
        private readonly DispatchUpdateStatusService $dispatchStatusService,
    ) {}

    public function execute(string $saleId, string $cancelledBy, ?string $reason = null, ?string $companyId = null): PosSale
    {
        return DB::transaction(function () use ($saleId, $cancelledBy, $reason, $companyId): PosSale {
            $sale = $this->saleRepository->findOrFail($saleId, $companyId);

            if ($sale->status === PosSale::STATUS_CANCELLED) {
                return $sale;
            }

            // Cancel linked dispatch if present (restores inventory to kardex)
            if ($sale->dispatch_id) {
                $this->dispatchStatusService->execute(
                    $sale->dispatch_id,
                    new UpdateStatusDispatchCommand(status: 'cancelled'),
                    $companyId
                );
            }

            // Cancel linked sales invoice if present
            if ($sale->sales_invoice_id) {
                $this->invoiceStatusService->execute(
                    $sale->sales_invoice_id,
                    new UpdateStatusSalesInvoiceCommand(status: 'cancelled'),
                    $companyId
                );
            }

            // Cancel POS sale
            $cancelled = $this->saleRepository->cancel($sale, $cancelledBy, $reason);

            // Update session counters
            if ($sale->session) {
                $sale->session->decrement('sales_count', 1);
                $sale->session->increment('cancelled_sales_count', 1);
                $sale->session->decrement('total_sales_amount', (float) $sale->total);
                $sale->session->decrement('total_discounts_amount', (float) $sale->discount_amount);
            }

            return $cancelled;
        });
    }
}
