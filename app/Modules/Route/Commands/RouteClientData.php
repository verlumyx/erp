<?php

declare(strict_types=1);

namespace App\Modules\Route\Commands;

/**
 * Un cliente fijo de la ruta tal como llega desde la pantalla.
 *
 * El `id` solo sirve para reconocer una fila que ya existe: nunca se usa para
 * insertar, así un id ajeno enviado desde el cliente no puede colisionar.
 */
class RouteClientData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $clientId,
        /** Vacía deja que la parada tome la dirección por defecto del cliente. */
        public readonly ?string $clientAddressId = null,
        public readonly int $sequence = 0,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row, int $index = 0): self
    {
        return new self(
            id: isset($row['id']) ? (string) $row['id'] : null,
            clientId: (string) $row['client_id'],
            clientAddressId: $row['client_address_id'] ?? null,
            /** Sin orden explícito manda la posición en la pantalla. */
            sequence: (int) ($row['sequence'] ?? $index + 1),
            status: (string) ($row['status'] ?? 'active'),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, self>
     */
    public static function collection(array $rows): array
    {
        return array_values(array_map(
            static fn (array $row, int $index): self => self::fromArray($row, $index),
            array_values($rows),
            array_keys(array_values($rows)),
        ));
    }
}
