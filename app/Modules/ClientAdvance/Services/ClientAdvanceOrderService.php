<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Services;

use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * La única condición del anticipo que no sale del formulario: que el pedido que
 * lo motiva sea de verdad de este cliente y de esta empresa.
 *
 * Vive aparte de los servicios de acción porque Crear y Actualizar la
 * necesitan igual: el anticipo se edita en borrador y cada guardado vuelve a
 * comprobar lo mismo.
 */
class ClientAdvanceOrderService
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $salesOrders,
    ) {}

    /**
     * El pedido es opcional —hay anticipos que no nacen de ninguno—, pero si
     * viene tiene que existir y pertenecer al mismo cliente.
     *
     * @throws ValidationException
     */
    public function guardSalesOrder(?string $salesOrderId, string $clientId, ?string $companyId): void
    {
        if (blank($salesOrderId)) {
            return;
        }

        $order = $this->salesOrders->findById($salesOrderId, $companyId);

        if (! $order instanceof SalesOrder) {
            throw ValidationException::withMessages([
                'sales_order_id' => 'El pedido de venta indicado no existe en esta empresa.',
            ]);
        }

        if ($order->client_id !== $clientId) {
            throw ValidationException::withMessages([
                'sales_order_id' => 'El pedido de venta pertenece a otro cliente.',
            ]);
        }
    }
}
