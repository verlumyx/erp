<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Commands;

/**
 * Una fila de `app_supplier_contacts` tal como llega desde la pantalla del proveedor.
 *
 * El `id` solo sirve para reconocer una fila que ya existe: nunca se usa para
 * insertar, así un id ajeno enviado desde el cliente no puede colisionar.
 */
class SupplierContactData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $name,
        public readonly string $isPrimary = 'no',
        public readonly ?string $position = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (string) $row['id'] : null,
            name: (string) $row['name'],
            isPrimary: ($row['is_primary'] ?? 'no') === 'yes' ? 'yes' : 'no',
            position: $row['position'] ?? null,
            email: $row['email'] ?? null,
            phone: $row['phone'] ?? null,
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
