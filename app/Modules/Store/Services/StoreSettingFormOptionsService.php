<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\ClientType\Models\ClientType;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogos que la pantalla de ajustes ofrece en sus selects: solo lo activo
 * de la empresa.
 */
class StoreSettingFormOptionsService
{
    /**
     * @return array{price_lists: array<int, array{value: string, label: string}>, warehouses: array<int, array{value: string, label: string}>, client_types: array<int, array{value: string, label: string}>}
     */
    public function execute(string $companyId): array
    {
        $option = fn (Model $model): array => [
            'value' => (string) $model->getKey(),
            'label' => (string) $model->getAttribute('name'),
        ];

        return [
            'price_lists' => PriceList::query()
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map($option)
                ->all(),
            'warehouses' => Warehouse::query()
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map($option)
                ->all(),
            'client_types' => ClientType::query()
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map($option)
                ->all(),
        ];
    }
}
