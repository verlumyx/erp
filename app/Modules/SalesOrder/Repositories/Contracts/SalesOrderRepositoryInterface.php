<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\SalesOrder\Commands\CreateSalesOrderCommand;
use App\Modules\SalesOrder\Commands\SearchSalesOrderCommand;
use App\Modules\SalesOrder\Commands\UpdateSalesOrderCommand;
use App\Modules\SalesOrder\Commands\UpdateStatusSalesOrderCommand;
use App\Modules\SalesOrder\Commands\WriteSalesOrderLineDispatchCommand;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;

interface SalesOrderRepositoryInterface
{
    public function create(CreateSalesOrderCommand $command, DocumentRatesData $rates): void;

    public function findById(string $id, ?string $companyId = null): ?SalesOrder;

    public function findOrFail(string $id, ?string $companyId = null): SalesOrder;

    public function update(SalesOrder $model, UpdateSalesOrderCommand $command, DocumentRatesData $rates): void;

    public function updateStatus(SalesOrder $model, UpdateStatusSalesOrderCommand $command): void;

    /** @return array{ data: SalesOrder[], total: int } */
    public function search(SearchSalesOrderCommand $command): array;

    /**
     * La línea del pedido con su fila bloqueada, para que dos despachos que la
     * sacan a la vez no la lean sin despachar.
     */
    /**
     * Las líneas vivas del pedido, en el orden en que se capturaron. Son las
     * únicas que reservan existencia.
     *
     * @return array<int, SalesOrderLine>
     */
    public function activeLines(SalesOrder $model): array;

    /**
     * Escribe lo reservado de la línea. Solo lo llama
     * `SalesOrderReservationService`.
     */
    public function writeLineReservation(SalesOrderLine $line, float $reservedQuantity): SalesOrderLine;

    public function lockLineById(string $id, ?string $companyId = null): ?SalesOrderLine;

    /**
     * Escribe el avance de despacho ya resuelto. Solo lo llama
     * `SalesOrderApplyDispatchService`.
     */
    public function writeLineDispatch(
        SalesOrderLine $line,
        WriteSalesOrderLineDispatchCommand $command,
    ): SalesOrderLine;

    /**
     * Recalcula `dispatched_percent` de la cabecera a partir de sus líneas
     * activas. Lo llama el mismo servicio, después de mover una línea.
     */
    public function refreshDispatchedPercent(string $orderId): void;
}
