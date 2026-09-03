<?php

declare(strict_types=1);

namespace App\Modules\Store\Repositories\Contracts;

use App\Modules\Store\Commands\CreateStoreItemCommand;
use App\Modules\Store\Commands\SearchStoreItemCommand;
use App\Modules\Store\Commands\UpdateStatusStoreItemCommand;
use App\Modules\Store\Commands\UpdateStoreItemCommand;
use App\Modules\Store\Models\StoreItem;

interface StoreItemRepositoryInterface
{
    public function create(CreateStoreItemCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?StoreItem;

    public function findOrFail(string $id, ?string $companyId = null): StoreItem;

    public function findByItem(string $itemId, string $companyId): ?StoreItem;

    public function update(StoreItem $model, UpdateStoreItemCommand $command): void;

    public function updateStatus(StoreItem $model, UpdateStatusStoreItemCommand $command): void;

    /** @return array{ data: StoreItem[], total: int } */
    public function search(SearchStoreItemCommand $command): array;

    /**
     * Lo que la tienda muestra: solo publicaciones visibles (`visibleInStore`),
     * con el orden del catálogo público. `price_list_id` es la lista con la
     * que se ordena por precio.
     *
     * @return array{ data: StoreItem[], total: int }
     */
    public function searchVisible(SearchStoreItemCommand $command, ?string $priceListId = null): array;

    public function findVisibleBySlug(string $companyId, string $slug): ?StoreItem;

    /**
     * Categorías activas con al menos una publicación visible y cuántas.
     *
     * @return array<int, array{id: string, name: string, count: int}>
     */
    public function visibleCategories(string $companyId): array;
}
