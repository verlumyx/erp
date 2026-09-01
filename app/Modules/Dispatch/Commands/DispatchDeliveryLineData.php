<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Commands;

/**
 * Lo que pasó con una línea al llegar al cliente: cuánto recibió y cuánto
 * devolvió en el mismo viaje.
 *
 * La línea se reconoce por su `id`, no por su posición: la entrega se registra
 * sobre un despacho ya guardado y sus líneas no se reordenan.
 */
class DispatchDeliveryLineData
{
    public function __construct(
        public readonly string $id,
        public readonly float $deliveredQuantity,
        /**
         * Cuánto de lo no entregado el cliente devolvió en mano. `null` da por
         * devuelta toda la diferencia, que es el caso normal.
         */
        public readonly ?float $returnedQuantity = null,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (string) $row['id'],
            deliveredQuantity: round((float) ($row['delivered_quantity'] ?? 0), 4),
            returnedQuantity: isset($row['returned_quantity']) && $row['returned_quantity'] !== ''
                ? round((float) $row['returned_quantity'], 4)
                : null,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, self>
     */
    public static function collection(array $rows): array
    {
        return array_map(static fn (array $row): self => self::fromArray($row), array_values($rows));
    }
}
