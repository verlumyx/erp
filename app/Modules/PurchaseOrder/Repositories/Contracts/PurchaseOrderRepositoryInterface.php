<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\PurchaseOrder\Commands\CreatePurchaseOrderCommand;
use App\Modules\PurchaseOrder\Commands\SearchPurchaseOrderCommand;
use App\Modules\PurchaseOrder\Commands\UpdatePurchaseOrderCommand;
use App\Modules\PurchaseOrder\Commands\UpdateStatusPurchaseOrderCommand;
use App\Modules\PurchaseOrder\Commands\WritePurchaseOrderLineInvoicedCommand;
use App\Modules\PurchaseOrder\Commands\WritePurchaseOrderLineReceiptCommand;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;

interface PurchaseOrderRepositoryInterface
{
    public function create(CreatePurchaseOrderCommand $command, DocumentRatesData $rates): void;

    public function findById(string $id, ?string $companyId = null): ?PurchaseOrder;

    public function findOrFail(string $id, ?string $companyId = null): PurchaseOrder;

    public function update(PurchaseOrder $model, UpdatePurchaseOrderCommand $command, DocumentRatesData $rates): void;

    public function updateStatus(PurchaseOrder $model, UpdateStatusPurchaseOrderCommand $command): void;

    /**
     * Las líneas vivas de la orden, en el orden en que se capturaron. Son las
     * únicas que anuncian mercancía en camino.
     *
     * @return array<int, PurchaseOrderLine>
     */
    public function activeLines(PurchaseOrder $model): array;

    /**
     * La línea con su fila bloqueada, para que dos entradas que reciben lo
     * mismo a la vez no lean el mismo pendiente.
     */
    public function lockLineById(string $id, ?string $companyId = null): ?PurchaseOrderLine;

    /**
     * Escribe lo recibido de la línea ya resuelto. Solo lo llama
     * `PurchaseOrderApplyReceiptService`.
     */
    public function writeLineReceipt(
        PurchaseOrderLine $line,
        WritePurchaseOrderLineReceiptCommand $command,
    ): PurchaseOrderLine;

    /** Recalcula el avance de recepción de la orden a partir de sus líneas. */
    public function refreshReceivedPercent(string $orderId): void;

    /**
     * Escribe lo facturado de la línea ya resuelto. Solo lo llama
     * `PurchaseOrderApplyInvoicedService`.
     */
    public function writeLineInvoiced(
        PurchaseOrderLine $line,
        WritePurchaseOrderLineInvoicedCommand $command,
    ): PurchaseOrderLine;

    /** Recalcula el avance de facturación de la orden a partir de sus líneas. */
    public function refreshInvoicedPercent(string $orderId): void;

    /** @return array{ data: PurchaseOrder[], total: int } */
    public function search(SearchPurchaseOrderCommand $command): array;
}
