<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Repositories\Contracts;

use App\Modules\ItemLot\Commands\CreateItemLotCommand;
use App\Modules\ItemLot\Commands\SearchItemLotCommand;
use App\Modules\ItemLot\Commands\UpdateItemLotCommand;
use App\Modules\ItemLot\Commands\UpdateStatusItemLotCommand;
use App\Modules\ItemLot\Models\ItemLot;

interface ItemLotRepositoryInterface
{
    public function create(CreateItemLotCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?ItemLot;

    public function findOrFail(string $id, ?string $companyId = null): ItemLot;

    public function update(ItemLot $model, UpdateItemLotCommand $command): void;

    public function updateStatus(ItemLot $model, UpdateStatusItemLotCommand $command): void;

    /** ¿Ya hay un lote con ese número para el artículo? */
    public function lotNumberExists(string $companyId, string $itemId, string $lotNumber): bool;

    /** @return array{ data: ItemLot[], total: int } */
    public function search(SearchItemLotCommand $command): array;
}
