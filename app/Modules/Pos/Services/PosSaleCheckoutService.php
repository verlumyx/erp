<?php

declare(strict_types=1);

namespace App\Modules\Pos\Services;

use App\Modules\Client\Models\Client;
use App\Modules\ClientCollection\Commands\ClientCollectionApplicationData;
use App\Modules\ClientCollection\Commands\CreateClientCollectionCommand;
use App\Modules\ClientCollection\Commands\UpdateStatusClientCollectionCommand;
use App\Modules\ClientCollection\Services\ClientCollectionCreateService;
use App\Modules\ClientCollection\Services\ClientCollectionUpdateStatusService;
use App\Modules\Dispatch\Commands\CreateDispatchCommand;
use App\Modules\Dispatch\Commands\DispatchLineData;
use App\Modules\Dispatch\Commands\UpdateStatusDispatchCommand;
use App\Modules\Dispatch\Services\DispatchCreateService;
use App\Modules\Dispatch\Services\DispatchUpdateStatusService;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\Pos\Commands\PosCheckoutCommand;
use App\Modules\Pos\Exceptions\PosSessionClosedException;
use App\Modules\Pos\Exceptions\PosUnauthorizedSessionException;
use App\Modules\Pos\Models\PosSale;
use App\Modules\Pos\Repositories\Contracts\PosSaleRepositoryInterface;
use App\Modules\Pos\Repositories\Contracts\PosSessionRepositoryInterface;
use App\Modules\SalesInvoice\Commands\CreateSalesInvoiceCommand;
use App\Modules\SalesInvoice\Commands\UpdateStatusSalesInvoiceCommand;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Models\SalesInvoiceLine;
use App\Modules\SalesInvoice\Services\SalesInvoiceCreateService;
use App\Modules\SalesInvoice\Services\SalesInvoiceUpdateStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosSaleCheckoutService
{
    public function __construct(
        private readonly PosSessionRepositoryInterface $sessionRepository,
        private readonly PosSaleRepositoryInterface $posSaleRepository,
        private readonly SalesInvoiceCreateService $invoiceCreateService,
        private readonly SalesInvoiceUpdateStatusService $invoiceStatusService,
        private readonly DispatchCreateService $dispatchCreateService,
        private readonly DispatchUpdateStatusService $dispatchStatusService,
        private readonly ClientCollectionCreateService $collectionCreateService,
        private readonly ClientCollectionUpdateStatusService $collectionStatusService,
        private readonly DocumentRatesResolverInterface $rates,
    ) {}

    public function execute(PosCheckoutCommand $command): PosSale
    {
        return DB::transaction(function () use ($command): PosSale {
            // 1. Validate session
            $session = $this->sessionRepository->findOrFail($command->posSessionId, $command->companyId);

            if (! $session->isOpen()) {
                throw new PosSessionClosedException;
            }

            if ($session->user_id !== $command->userId) {
                throw new PosUnauthorizedSessionException;
            }

            // Remove held draft if this checkout is finalizing a held ticket
            $draftSale = PosSale::where('id', $command->id)
                ->where('status', PosSale::STATUS_DRAFT)
                ->first();
            if ($draftSale) {
                $draftSale->delete();
            }

            // 2. Resolve document exchange rate
            $rates = $this->rates->forDocument(
                $command->companyId,
                $command->currency,
                now()->toDateString(),
                $command->exchangeRate !== null ? (string) $command->exchangeRate : null,
            );

            // 3. Step 1: Create SalesInvoice (FVE)
            $invoiceId = (string) Str::uuid();
            $invoice = $this->invoiceCreateService->execute(new CreateSalesInvoiceCommand(
                id: $invoiceId,
                companyId: $command->companyId,
                clientId: $command->clientId,
                warehouseId: $session->register->warehouse_id,
                invoiceDate: now()->toDateString(),
                dueDate: now()->toDateString(),
                createdBy: $command->userId,
                lines: $command->toInvoiceLines(),
                sourceableType: null,
                sourceableId: null,
                invoiceSeries: $session->register->invoice_series,
                saleType: 'cash',
                currency: $command->currency,
                exchangeRateOverride: (string) $rates->exchangeRate,
                notes: $command->notes,
            ));

            // 4. Step 2: Evaluate stockable items and generate Dispatch (DES) if needed
            $invoice->load(['lines.item']);
            $stockableLines = $invoice->lines->filter(function ($line) {
                return $line->item?->movesStock() ?? false;
            });

            $dispatch = null;
            if ($stockableLines->isNotEmpty()) {
                $dispatchLines = [];
                foreach ($stockableLines as $invLine) {
                    $dispatchLines[] = new DispatchLineData(
                        id: null,
                        itemId: $invLine->item_id,
                        measurementUnitId: $invLine->measurement_unit_id,
                        quantity: (float) $invLine->quantity,
                        unitPrice: (float) $invLine->unit_price,
                        discountPercent: (float) $invLine->discount_percent,
                        discountAmount: (float) $invLine->discount_amount,
                        taxPercent: (float) $invLine->tax_percent,
                        taxAmount: (float) $invLine->tax_amount,
                        withholdingPercent: 0.0,
                        withholdingAmount: 0.0,
                        subtotal: (float) $invLine->subtotal,
                        total: (float) $invLine->total,
                        taxId: $invLine->tax_id,
                        sourceableType: SalesInvoiceLine::MORPH_ALIAS,
                        sourceableId: $invLine->id,
                        lots: [],
                        serials: [],
                        locationId: null,
                        notes: null,
                        status: 'active',
                    );
                }

                $dispatch = $this->dispatchCreateService->execute(new CreateDispatchCommand(
                    id: (string) Str::uuid(),
                    companyId: $command->companyId,
                    recipientType: Client::MORPH_ALIAS,
                    recipientId: $command->clientId,
                    warehouseId: $session->register->warehouse_id,
                    dispatchDate: now()->toDateString(),
                    createdBy: $command->userId,
                    lines: $dispatchLines,
                    sourceableType: SalesInvoice::MORPH_ALIAS,
                    sourceableId: $invoice->id,
                ));

                // Confirm dispatch to reduce stock in kardex
                $this->dispatchStatusService->execute(
                    $dispatch->id,
                    new UpdateStatusDispatchCommand(status: 'confirmed'),
                    $command->companyId
                );

                $invoice->update(['dispatch_id' => $dispatch->id]);
            }

            // 5. Confirm SalesInvoice (freezes costs and generates receivable)
            $this->invoiceStatusService->execute(
                $invoice->id,
                new UpdateStatusSalesInvoiceCommand(status: 'confirmed'),
                $command->companyId
            );

            // 6. Step 3: Register Collections (COB) for each payment
            foreach ($command->payments as $payment) {
                if ($payment->amountApplied <= 0) {
                    continue;
                }

                $collectionPaymentMethod = match ($payment->paymentMethod) {
                    'cash' => 'cash',
                    'card_debit' => 'card_debit',
                    'card_credit' => 'card_credit',
                    'mobile_payment' => 'mobile_payment',
                    'biopago' => 'biopago',
                    'transfer' => 'transfer',
                    'credit_note' => 'credit_note',
                    'check' => 'check',
                    'card' => 'card',
                    default => 'other',
                };

                $collection = $this->collectionCreateService->execute(new CreateClientCollectionCommand(
                    id: (string) Str::uuid(),
                    companyId: $command->companyId,
                    clientId: $command->clientId,
                    collectionDate: now()->toDateString(),
                    createdBy: $command->userId,
                    applications: [
                        new ClientCollectionApplicationData(
                            salesInvoiceId: $invoice->id,
                            appliedAmount: $payment->amountInDocumentCurrency,
                        ),
                    ],
                    originType: 'invoice',
                    originId: $invoice->id,
                    paymentMethod: $collectionPaymentMethod,
                    reference: $payment->reference,
                    currency: $payment->currency,
                    exchangeRateOverride: (string) $payment->exchangeRate,
                    amount: $payment->amountApplied,
                ));

                $this->collectionStatusService->execute(
                    $collection->id,
                    new UpdateStatusClientCollectionCommand(status: 'confirmed'),
                    $command->companyId
                );
            }

            // 7. Step 4: Record POS Sale Ticket
            $posSale = $this->posSaleRepository->create(
                $command,
                $invoice->id,
                $dispatch?->id,
                (float) $rates->exchangeRate
            );

            // Ensure pos_register_id matches the session's register
            $posSale->update(['pos_register_id' => $session->pos_register_id]);

            // 8. Accumulate in current session
            $this->sessionRepository->accumulateSale(
                $session,
                (float) $posSale->total,
                (float) $posSale->discount_amount,
                $command->payments
            );

            return $this->posSaleRepository->findOrFail($posSale->id, $command->companyId);
        });
    }
}
