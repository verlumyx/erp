<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Services;

use App\Modules\SupplierType\Commands\SearchSupplierTypeCommand;
use App\Modules\SupplierType\Models\SupplierType;
use App\Modules\SupplierType\Repositories\Contracts\SupplierTypeRepositoryInterface;

/**
 * Catálogos que alimentan los selects del formulario de proveedor.
 *
 * Se resuelven a través de los repositorios de sus módulos: el módulo de
 * proveedores nunca consulta sus tablas directamente.
 */
class SupplierFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly SupplierTypeRepositoryInterface $supplierTypes,
    ) {}

    /**
     * @return array{ supplierTypes: array<int, array{id: string, name: string}> }
     */
    public function execute(?string $companyId): array
    {
        $supplierTypes = $this->supplierTypes->search(new SearchSupplierTypeCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        return [
            'supplierTypes' => array_map(
                fn (SupplierType $supplierType): array => [
                    'id' => $supplierType->id,
                    'name' => $supplierType->name,
                ],
                $supplierTypes['data'],
            ),
        ];
    }
}
