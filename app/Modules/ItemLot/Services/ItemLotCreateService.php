<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Services;

use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\ItemLot\Commands\CreateItemLotCommand;
use App\Modules\ItemLot\Exceptions\DuplicateItemLotNumberException;
use App\Modules\ItemLot\Exceptions\InvalidItemLotDatesException;
use App\Modules\ItemLot\Exceptions\ItemLotNotTrackableException;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemLot\Repositories\Contracts\ItemLotRepositoryInterface;

/**
 * Alta de un lote.
 *
 * No hay pantalla de creación: el lote nace en el documento que recibe la
 * mercancía. Por eso las invariantes viven aquí y no en un `FormRequest` — sin
 * formulario delante, este servicio es el único guardián que le queda al lote,
 * y quien lo llame (Entrada, Factura de compra) las hereda sin repetirlas.
 */
class ItemLotCreateService
{
    public function __construct(
        private readonly ItemLotRepositoryInterface $repository,
        private readonly ItemRepositoryInterface $itemRepository,
    ) {}

    public function execute(CreateItemLotCommand $command): ItemLot
    {
        $this->guardItemIsTrackable($command);
        $this->guardLotNumberIsFree($command);
        $this->guardDatesAreConsistent($command);

        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }

    /**
     * El artículo tiene que existir en la empresa y ser de un tipo que afecte
     * inventario: un servicio no se recibe por lotes.
     */
    private function guardItemIsTrackable(CreateItemLotCommand $command): void
    {
        $item = $this->itemRepository->findById($command->itemId, $command->companyId);

        if ($item === null || ! in_array($item->type, ItemLot::TRACKABLE_ITEM_TYPES, true)) {
            throw new ItemLotNotTrackableException;
        }
    }

    private function guardLotNumberIsFree(CreateItemLotCommand $command): void
    {
        if ($this->repository->lotNumberExists($command->companyId, $command->itemId, $command->lotNumber)) {
            throw new DuplicateItemLotNumberException;
        }
    }

    private function guardDatesAreConsistent(CreateItemLotCommand $command): void
    {
        if ($command->manufacturedAt === null || $command->expiresAt === null) {
            return;
        }

        if ($command->expiresAt < $command->manufacturedAt) {
            throw new InvalidItemLotDatesException;
        }
    }
}
