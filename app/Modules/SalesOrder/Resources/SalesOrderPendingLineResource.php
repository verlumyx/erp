<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Resources;

use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\SalesOrder\Services\SalesOrderPendingLinesService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Línea de un pedido de venta con saldo, vista por la pantalla que va a
 * cubrirlo.
 *
 * Manda los dos avances —lo despachado y lo facturado— pase lo que pase, y
 * además `pending_quantity` ya resuelto contra el que preguntó: así el despacho
 * lee lo que falta por salir y la factura lo que falta por cobrar sin repetir
 * la resta en el frontend. El precio, el descuento y el impuesto viajan
 * congelados del pedido: son las condiciones pactadas con el cliente y no se
 * revalúan.
 *
 * Se calcula aquí y no se lee de la columna `pending_quantity` de la línea:
 * esa es solo el pendiente por despachar, y no sirve para facturar.
 *
 * Las relaciones las garantiza `SalesOrderPendingLinesService`.
 */
class SalesOrderPendingLineResource extends JsonResource
{
    /**
     * @param  SalesOrderPendingLinesService::AGAINST_*  $against
     */
    public function __construct(
        SalesOrderLine $resource,
        private readonly string $against,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'line_number' => $this->line_number,
            'item_id' => $this->item_id,
            'item_sku' => $this->item?->sku,
            'item_name' => $this->item?->name,
            'measurement_unit_id' => $this->measurement_unit_id,
            'measurement_unit_name' => $this->measurementUnit?->name,
            'quantity' => (string) $this->quantity,
            'dispatched_quantity' => (string) $this->dispatched_quantity,
            'invoiced_quantity' => (string) $this->invoiced_quantity,
            'pending_quantity' => number_format($this->pendingQuantity(), 4, '.', ''),
            'unit_price' => (string) $this->unit_price,
            'discount_percent' => (string) $this->discount_percent,
            'tax_id' => $this->tax_id,
            'tax_percent' => (string) $this->tax_percent,
            'withholding_percent' => (string) $this->withholding_percent,
            'notes' => $this->notes,
        ];
    }

    /** Lo pedido menos el avance por el que preguntaron, nunca negativo. */
    private function pendingQuantity(): float
    {
        return max(round((float) $this->quantity - (float) $this->{$this->against}, 4), 0);
    }
}
