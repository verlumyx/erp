<?php

declare(strict_types=1);

namespace App\Modules\Store\Repositories\Contracts;

use App\Modules\Client\Models\ClientAddress;
use App\Modules\Store\Commands\CreateStoreOrderCommand;
use App\Modules\Store\Commands\SearchStoreOrderCommand;
use App\Modules\Store\Models\StoreItem;
use App\Modules\Store\Models\StoreOrder;

interface StoreOrderRepositoryInterface
{
    /** Las líneas del comando ya vienen valoradas por el servicio. */
    public function create(CreateStoreOrderCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?StoreOrder;

    public function findOrFail(string $id, ?string $companyId = null): StoreOrder;

    public function findByCode(string $companyId, string $code): ?StoreOrder;

    /** @return array{ data: StoreOrder[], total: int } */
    public function search(SearchStoreOrderCommand $command): array;

    public function countPending(string $companyId): int;

    public function writeConverted(StoreOrder $model, string $salesOrderId, string $clientId, string $convertedBy): void;

    public function writeRejected(StoreOrder $model, string $reason): void;

    /*
     * Lecturas del ERP que solo el pedido web necesita. Viven aquí para que
     * los módulos de origen no ganen consultas por la tienda.
     */

    /**
     * Publicaciones visibles de la empresa, indexadas por `slug`, con su artículo.
     *
     * @param  array<int, string>  $slugs
     * @return array<string, StoreItem>
     */
    public function visibleStoreItemsBySlug(string $companyId, array $slugs): array;

    /**
     * Unidad base de cada artículo, indexada por `item_id`.
     *
     * @param  array<int, string>  $itemIds
     * @return array<string, string>
     */
    public function baseUnitIdsFor(string $companyId, array $itemIds): array;

    public function findActiveClientAddress(string $clientId, string $addressId): ?ClientAddress;

    /** Crea en el cliente la dirección que el comprador escribió en el pedido. */
    public function createDeliveryAddress(StoreOrder $order, string $clientId): ClientAddress;

    public function firstActiveWarehouseId(string $companyId): ?string;

    /**
     * Porcentajes de los impuestos dados, indexados por id.
     *
     * @param  array<int, string>  $taxIds
     * @return array<string, array{percentage: string, withholding_percentage: string}>
     */
    public function taxRates(array $taxIds): array;
}
