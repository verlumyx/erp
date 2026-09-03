<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Store\Exceptions\StoreItemNotFoundException;
use App\Modules\Store\Models\StoreItem;
use App\Modules\Store\Repositories\Contracts\StoreItemRepositoryInterface;

/**
 * Trae la publicación con lo que la pantalla de edición muestra en modo
 * lectura desde el artículo: precio en la lista de la tienda y
 * disponibilidad. Van como atributos sueltos porque no viven en la tabla.
 */
class StoreItemFindService
{
    public function __construct(
        private readonly StoreItemRepositoryInterface $repository,
        private readonly StoreSettingFindOrCreateService $settings,
        private readonly StoreCatalogService $catalog,
    ) {}

    public function execute(string $id, ?string $companyId = null): StoreItem
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new StoreItemNotFoundException;
        }

        $settings = $this->settings->execute((string) $model->company_id);
        $itemId = (string) $model->item_id;

        $prices = $this->catalog->pricesFor($settings, $settings->price_list_id, [$itemId]);
        $availability = $this->catalog->availabilityFor($settings, [$itemId]);

        $model->setAttribute('store_price', $prices[$itemId] ?? null);
        $model->setAttribute('store_availability', [
            'in_stock' => ($availability[$itemId] ?? 0.0) > 0 ? 'yes' : 'no',
            'quantity' => number_format(max($availability[$itemId] ?? 0.0, 0), 4, '.', ''),
        ]);

        return $model;
    }
}
