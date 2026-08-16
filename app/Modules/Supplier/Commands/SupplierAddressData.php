<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Commands;

/**
 * Una fila de `app_supplier_addresses` tal como llega desde la pantalla del proveedor.
 *
 * El `id` solo sirve para reconocer una fila que ya existe: nunca se usa para
 * insertar, así un id ajeno enviado desde el cliente no puede colisionar.
 */
class SupplierAddressData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $type,
        public readonly string $address,
        public readonly string $isDefault = 'no',
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $country = null,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (string) $row['id'] : null,
            type: (string) ($row['type'] ?? 'billing'),
            address: (string) $row['address'],
            isDefault: ($row['is_default'] ?? 'no') === 'yes' ? 'yes' : 'no',
            city: $row['city'] ?? null,
            state: $row['state'] ?? null,
            country: $row['country'] ?? null,
            status: (string) ($row['status'] ?? 'active'),
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
