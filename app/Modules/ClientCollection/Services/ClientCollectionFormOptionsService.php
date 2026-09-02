<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Services;

use App\Modules\User\Commands\SearchUserCommand;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;

/**
 * Catálogos que alimentan los selects del cobro.
 *
 * Los clientes y las facturas NO viajan aquí: los dos padrones son demasiado
 * grandes para las props de cada pantalla. La cabecera busca el cliente contra
 * `clients.lookup` y la factura de origen contra `sales-invoices.lookup`, las
 * dos con `Select2Ajax`: la opción elegida ya trae lo que la pantalla necesita.
 */
class ClientCollectionFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(?string $companyId): array
    {
        return ['collectors' => $this->collectorOptions($companyId)];
    }

    /**
     * Quién puede figurar como cobrador: cualquier usuario de la empresa.
     *
     * La ruta no viaja aquí: el padrón de rutas se busca contra
     * `routes.lookup` con `Select2Ajax`, igual que el cliente y la factura.
     *
     * @return array<int, array{id: string, name: string}>
     */
    private function collectorOptions(?string $companyId): array
    {
        $result = $this->users->search(new SearchUserCommand(
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        return array_map(
            fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            $result['data'],
        );
    }
}
