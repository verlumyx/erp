<?php

declare(strict_types=1);

namespace App\Modules\Client\Services;

use App\Modules\Client\Commands\CreateClientCommand;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;
use App\Modules\PriceList\Models\PriceList;
use Illuminate\Support\Str;

class ClientProvisionService
{
    public const DEFAULT_CLIENT_NAME = 'Consumidor Final';
    public const DEFAULT_DOCUMENT_TYPE = 'V';
    public const DEFAULT_DOCUMENT_NUMBER = '0000000';

    public function __construct(
        private readonly ClientRepositoryInterface $repository,
    ) {}

    /**
     * Provision default "Consumidor Final" client for a company if it does not already exist.
     */
    public function provisionDefaults(string $companyId, ?string $userId = null, ?string $priceListId = null): Client
    {
        $existing = Client::query()
            ->where('company_id', $companyId)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('document_type', self::DEFAULT_DOCUMENT_TYPE)
                        ->where('document_number', self::DEFAULT_DOCUMENT_NUMBER);
                })->orWhereRaw('LOWER(name) = ?', [strtolower(self::DEFAULT_CLIENT_NAME)]);
            })
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        if ($priceListId === null) {
            $priceListId = PriceList::query()
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->value('id');
        }

        $id = Str::uuid7()->toString();

        $this->repository->create(new CreateClientCommand(
            id: $id,
            companyId: $companyId,
            name: self::DEFAULT_CLIENT_NAME,
            documentType: self::DEFAULT_DOCUMENT_TYPE,
            documentNumber: self::DEFAULT_DOCUMENT_NUMBER,
            createdBy: $userId,
            priceListId: $priceListId,
            legalName: self::DEFAULT_CLIENT_NAME,
            notes: 'Cliente general de mostrador y ventas rápidas',
        ));

        return $this->repository->findOrFail($id, $companyId);
    }
}
