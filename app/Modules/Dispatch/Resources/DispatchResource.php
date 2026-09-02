<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            /**
             * A quién va la mercancía. Un despacho de venta la lleva a un
             * cliente; uno que sirve un traslado, a otra bodega propia.
             */
            'recipient_type' => $this->recipient_type,
            'recipient_id' => $this->recipient_id,
            'recipient_name' => $this->whenLoaded('recipient', fn () => $this->recipient?->name),
            'recipient_code' => $this->whenLoaded('recipient', fn () => $this->recipient?->code),
            'sourceable_type' => $this->sourceable_type,
            'sourceable_id' => $this->sourceable_id,
            /** Etiqueta legible del origen, para no ir a buscarlo desde la pantalla. */
            'sourceable_code' => $this->whenLoaded('sourceable', fn () => $this->sourceable?->code),
            'client_address_id' => $this->client_address_id,
            'client_address_name' => $this->whenLoaded('clientAddress', fn () => $this->clientAddress?->name),
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'route_id' => $this->route_id,
            'route_code' => $this->whenLoaded('deliveryRoute', fn () => $this->deliveryRoute?->code),
            'route_name' => $this->whenLoaded('deliveryRoute', fn () => $this->deliveryRoute?->name),
            /** La parada la escribe la planificación de la ruta, no esta pantalla. */
            'route_stop_id' => $this->route_stop_id,
            'dispatch_date' => $this->dispatch_date?->format('Y-m-d'),
            'delivery_date' => $this->delivery_date?->format('Y-m-d'),
            'driver_id' => $this->driver_id,
            'driver_name' => $this->whenLoaded('driver', fn () => $this->driver?->name),
            'vehicle_plate' => $this->vehicle_plate,
            'carrier' => $this->carrier,
            'tracking_number' => $this->tracking_number,
            'freight_amount' => $this->freight_amount,
            'total_quantity' => $this->total_quantity,
            'total_weight' => $this->total_weight,
            'total_volume' => $this->total_volume,
            'total_cost' => $this->total_cost,
            'delivery_status' => $this->delivery_status,
            'received_by_name' => $this->received_by_name,
            'received_by_document' => $this->received_by_document,
            'signature_path' => $this->signature_path,
            'evidence_path' => $this->evidence_path,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'rejection_reason' => $this->rejection_reason,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'lines' => $this->whenLoaded(
                'lines',
                fn (): array => DispatchLineResource::collection($this->lines)->resolve($request),
            ),
        ];
    }
}
