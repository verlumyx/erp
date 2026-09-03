<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Item\Models\Item;
use App\Modules\Store\Commands\CreateStoreItemCommand;
use App\Modules\Store\Exceptions\ItemAlreadyPublishedException;
use App\Modules\Store\Models\StoreItem;
use App\Modules\Store\Repositories\Contracts\StoreItemRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Publica un artículo del ERP en la tienda.
 *
 * Solo se publica lo vendible y activo, y una sola vez por empresa. El
 * título y la descripción estrenan con los del artículo cuando la pantalla
 * no los manda.
 */
class StoreItemCreateService
{
    public function __construct(
        private readonly StoreItemRepositoryInterface $repository,
    ) {}

    /**
     * @throws ValidationException
     * @throws ItemAlreadyPublishedException
     */
    public function execute(CreateStoreItemCommand $command): StoreItem
    {
        $item = $this->publishableItem($command);

        if ($this->repository->findByItem($item->id, $command->companyId) instanceof StoreItem) {
            throw new ItemAlreadyPublishedException;
        }

        $this->repository->create(new CreateStoreItemCommand(
            id: $command->id,
            companyId: $command->companyId,
            itemId: $command->itemId,
            createdBy: $command->createdBy,
            title: $command->title ?? (string) $item->name,
            slug: $command->slug,
            summary: $command->summary,
            description: $command->description ?? $item->description,
            isFeatured: $command->isFeatured,
            order: $command->order,
        ));

        return $this->repository->findOrFail($command->id, $command->companyId);
    }

    /**
     * @throws ValidationException
     */
    private function publishableItem(CreateStoreItemCommand $command): Item
    {
        $item = Item::query()
            ->where('company_id', $command->companyId)
            ->find($command->itemId);

        if (! $item instanceof Item) {
            throw ValidationException::withMessages([
                'item_id' => 'El artículo no existe en esta empresa.',
            ]);
        }

        if ($item->status !== 'active') {
            throw ValidationException::withMessages([
                'item_id' => 'Solo se publican artículos activos.',
            ]);
        }

        if ($item->is_sellable !== 'yes') {
            throw ValidationException::withMessages([
                'item_id' => 'Solo se publican artículos vendibles.',
            ]);
        }

        return $item;
    }
}
