<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Repositories\Contracts;

use App\Modules\ItemSerial\Commands\CreateItemSerialCommand;
use App\Modules\ItemSerial\Commands\SearchItemSerialCommand;
use App\Modules\ItemSerial\Commands\UpdateItemSerialCommand;
use App\Modules\ItemSerial\Commands\UpdateStatusItemSerialCommand;
use App\Modules\ItemSerial\Models\ItemSerial;

interface ItemSerialRepositoryInterface
{
    public function create(CreateItemSerialCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?ItemSerial;

    public function findOrFail(string $id, ?string $companyId = null): ItemSerial;

    public function update(ItemSerial $model, UpdateItemSerialCommand $command): void;

    public function updateStatus(ItemSerial $model, UpdateStatusItemSerialCommand $command): void;

    /** ¿Ya hay una serie con ese número para el artículo? */
    public function serialNumberExists(string $companyId, string $itemId, string $serialNumber): bool;

    /** @return array{ data: ItemSerial[], total: int } */
    public function search(SearchItemSerialCommand $command): array;
}
