<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Línea de una orden de compra vista como candidata a facturar.
 *
 * Lleva las tres cantidades juntas —lo pedido, lo ya facturado y lo que queda—
 * para que la factura nazca con el saldo y el usuario vea de dónde sale. El
 * precio, el descuento y el impuesto viajan congelados de la orden: son las
 * condiciones que se pactaron con el proveedor.
 *
 * `pending_quantity` se calcula aquí y no se lee de la columna del mismo
 * nombre: esa columna es el pendiente por *recibir*, otra cuenta.
 *
 * Las relaciones las garantiza `PurchaseOrderInvoiceableLinesService`.
 */
class PurchaseOrderInvoiceableLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'line_number' => $this->line_number,
            'item_id' => $this->item_id,
            'item_code' => $this->item?->code,
            'item_name' => $this->item?->name,
            'measurement_unit_id' => $this->measurement_unit_id,
            'measurement_unit_name' => $this->measurementUnit?->name,
            'quantity' => (string) $this->quantity,
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

    /** Lo pedido menos lo ya facturado, nunca negativo. */
    private function pendingQuantity(): float
    {
        return max(round((float) $this->quantity - (float) $this->invoiced_quantity, 4), 0);
    }
}
