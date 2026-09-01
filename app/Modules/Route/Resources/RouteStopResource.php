<?php

declare(strict_types=1);

namespace App\Modules\Route\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteStopResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'route_id' => $this->route_id,
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'client_code' => $this->whenLoaded('client', fn () => $this->client?->code),
            'client_address_id' => $this->client_address_id,
            'client_address_name' => $this->whenLoaded('clientAddress', fn () => $this->clientAddress?->name),
            'client_address' => $this->whenLoaded('clientAddress', fn () => $this->clientAddress?->address),
            'stop_date' => $this->stop_date?->format('Y-m-d'),
            'sequence' => $this->sequence,
            'estimated_arrival' => $this->estimated_arrival,
            'actual_arrival' => $this->actual_arrival?->format('Y-m-d H:i:s'),
            'actual_departure' => $this->actual_departure?->format('Y-m-d H:i:s'),
            'stop_status' => $this->stop_status,
            'skip_reason' => $this->skip_reason,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
        ];
    }
}
