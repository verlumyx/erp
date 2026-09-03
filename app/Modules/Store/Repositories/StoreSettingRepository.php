<?php

declare(strict_types=1);

namespace App\Modules\Store\Repositories;

use App\Modules\Company\Models\Company;
use App\Modules\Store\Commands\UpdateStoreSettingsCommand;
use App\Modules\Store\Models\StoreSetting;
use App\Modules\Store\Repositories\Contracts\StoreSettingRepositoryInterface;

class StoreSettingRepository implements StoreSettingRepositoryInterface
{
    public function create(string $companyId, ?string $createdBy): void
    {
        StoreSetting::create([
            'company_id' => $companyId,
            /** El nombre de la tienda estrena con el de la empresa. */
            'store_name' => (string) (Company::query()->whereKey($companyId)->value('name') ?? 'Tienda'),
            'created_by' => $createdBy,
        ]);
    }

    public function findByCompany(string $companyId): ?StoreSetting
    {
        return StoreSetting::query()
            ->with(['priceList', 'warehouse', 'defaultClientType'])
            ->where('company_id', $companyId)
            ->first();
    }

    public function findOrFailByCompany(string $companyId): StoreSetting
    {
        return StoreSetting::query()
            ->with(['priceList', 'warehouse', 'defaultClientType'])
            ->where('company_id', $companyId)
            ->firstOrFail();
    }

    public function findByApiKeyHash(string $hash): ?StoreSetting
    {
        return StoreSetting::query()
            ->with('company')
            ->where('api_key_hash', $hash)
            ->first();
    }

    public function update(StoreSetting $model, UpdateStoreSettingsCommand $command): void
    {
        $model->update([
            'is_enabled' => $command->isEnabled,
            'store_name' => $command->storeName,
            'brand_color' => $command->brandColor,
            'price_list_id' => $command->priceListId,
            'warehouse_id' => $command->warehouseId,
            'shows_stock' => $command->showsStock,
            'allows_orders' => $command->allowsOrders,
            'default_client_type_id' => $command->defaultClientTypeId,
            'shows_secondary_currency' => $command->showsSecondaryCurrency,
            'contact_phone' => $command->contactPhone,
            'contact_email' => $command->contactEmail,
            'store_url' => $command->storeUrl,
        ]);
    }

    public function writeApiKeyHash(StoreSetting $model, string $hash): void
    {
        $model->update([
            'api_key_hash' => $hash,
            'api_key_last_used_at' => null,
        ]);
    }

    public function writeLogoPath(StoreSetting $model, ?string $path): void
    {
        $model->update(['logo_path' => $path]);
    }

    public function touchApiKeyLastUsedAt(StoreSetting $model): void
    {
        $model->update(['api_key_last_used_at' => now()]);
    }
}
