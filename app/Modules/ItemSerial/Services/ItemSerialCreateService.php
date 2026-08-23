<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Services;

use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\ItemLot\Repositories\Contracts\ItemLotRepositoryInterface;
use App\Modules\ItemSerial\Commands\CreateItemSerialCommand;
use App\Modules\ItemSerial\Exceptions\DuplicateItemSerialNumberException;
use App\Modules\ItemSerial\Exceptions\ItemSerialLotMismatchException;
use App\Modules\ItemSerial\Exceptions\ItemSerialNotTrackableException;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\ItemSerial\Repositories\Contracts\ItemSerialRepositoryInterface;

/**
 * Alta de una serie.
 *
 * No hay pantalla de creación: la serie nace en el documento que recibe la
 * mercancía. Por eso las invariantes viven aquí y no en un `FormRequest` — sin
 * formulario delante, este servicio es el único guardián que le queda a la
 * serie, y quien lo llame (Entrada, Factura de compra) las hereda.
 */
class ItemSerialCreateService
{
    public function __construct(
        private readonly ItemSerialRepositoryInterface $repository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly ItemLotRepositoryInterface $lotRepository,
    ) {}

    public function execute(CreateItemSerialCommand $command): ItemSerial
    {
        $this->guardItemIsSerialized($command);
        $this->guardSerialNumberIsFree($command);
        $this->guardLotBelongsToItem($command);

        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }

    private function guardItemIsSerialized(CreateItemSerialCommand $command): void
    {
        $item = $this->itemRepository->findById($command->itemId, $command->companyId);

        if ($item === null || $item->type !== ItemSerial::TRACKABLE_ITEM_TYPE) {
            throw new ItemSerialNotTrackableException;
        }
    }

    private function guardSerialNumberIsFree(CreateItemSerialCommand $command): void
    {
        if ($this->repository->serialNumberExists($command->companyId, $command->itemId, $command->serialNumber)) {
            throw new DuplicateItemSerialNumberException;
        }
    }

    /**
     * Una serie puede no tener lote, pero si lo tiene ha de ser un lote del
     * mismo artículo: la trazabilidad no cruza artículos.
     */
    private function guardLotBelongsToItem(CreateItemSerialCommand $command): void
    {
        if ($command->lotId === null) {
            return;
        }

        $lot = $this->lotRepository->findById($command->lotId, $command->companyId);

        if ($lot === null || $lot->item_id !== $command->itemId) {
            throw new ItemSerialLotMismatchException;
        }
    }
}
