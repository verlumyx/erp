<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Services;

use App\Modules\PriceList\Commands\CreatePriceListCommand;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\PriceList\Repositories\Contracts\PriceListRepositoryInterface;
use Illuminate\Support\Str;

class PriceListProvisionService
{
    public const DEFAULT_PRICE_LIST_NAME = 'Precio regular';

    public function __construct(
        private readonly PriceListRepositoryInterface $repository,
    ) {}

    /**
     * Provision default price list for a company if it does not already exist.
     */
    public function provisionDefaults(string $companyId, ?string $userId = null): PriceList
    {
        $existing = PriceList::query()
            ->where('company_id', $companyId)
            ->whereRaw('LOWER(name) = ?', [strtolower(self::DEFAULT_PRICE_LIST_NAME)])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $id = Str::uuid7()->toString();

        $this->repository->create(new CreatePriceListCommand(
            id: $id,
            companyId: $companyId,
            name: self::DEFAULT_PRICE_LIST_NAME,
            createdBy: $userId,
            description: 'Lista de precios regular por defecto para ventas generales',
        ));

        return $this->repository->findOrFail($id, $companyId);
    }
}
