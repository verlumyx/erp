<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Configuration\Services\ConfigurationFindService;
use App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException;
use App\Modules\ExchangeRate\Services\Contracts\ExchangeRateResolverInterface;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemPrice;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Models\StoreItem;
use App\Modules\Store\Models\StoreItemImage;
use App\Modules\Store\Models\StoreSetting;
use Illuminate\Database\Eloquent\Collection;

/**
 * Arma lo que la tienda muestra de un artículo: precio, precio en la moneda
 * secundaria, disponibilidad, categoría y unidad base. Nada de eso se guarda
 * en la publicación; se lee del ERP en el momento de servir la API.
 *
 * El precio sale de la lista de la tienda o, si el comprador está vinculado a
 * un cliente con lista propia, de esa lista. La tasa se resuelve con el mismo
 * servicio que usan los documentos: no se inventa otra.
 */
class StoreCatalogService
{

    public function __construct(
        private readonly ExchangeRateResolverInterface $rates,
        private readonly ConfigurationFindService $configurations,
    ) {}

    /**
     * Lista de precio con la que ve la tienda este comprador: la de su
     * cliente si la tiene; si no, la de la tienda. `null` = sin precios.
     */
    public function priceListFor(StoreSetting $settings, ?StoreCustomer $customer = null): ?string
    {
        $clientPriceList = $customer?->isLinked() ? $customer->client?->price_list_id : null;

        return $clientPriceList ?? $settings->price_list_id;
    }

    /**
     * Un producto de la tienda con la forma de `StoreProductResource`.
     *
     * @return array<string, mixed>
     */
    public function product(StoreSetting $settings, StoreItem $storeItem, ?StoreCustomer $customer = null): array
    {
        return $this->products($settings, new Collection([$storeItem]), $customer)[0];
    }

    /**
     * Varios productos con una sola consulta de precios, unidades y
     * existencias. Es lo que usa el listado.
     *
     * @param  Collection<int, StoreItem>  $storeItems
     * @return array<int, array<string, mixed>>
     */
    public function products(StoreSetting $settings, Collection $storeItems, ?StoreCustomer $customer = null): array
    {
        $storeItems->loadMissing(['item.category', 'activeImages']);

        $itemIds = $storeItems->map(fn (StoreItem $storeItem): string => (string) $storeItem->item_id)->all();

        $prices = $this->pricesFor($settings, $this->priceListFor($settings, $customer), $itemIds);
        $units = $this->baseUnitsFor((string) $settings->company_id, $itemIds);
        $availability = $this->availabilityFor($settings, $itemIds);
        $secondary = $this->secondaryCurrency($settings);

        return $storeItems->map(function (StoreItem $storeItem) use ($settings, $prices, $units, $availability, $secondary): array {
            $item = $storeItem->item;
            $price = $prices[$storeItem->item_id] ?? null;

            return [
                'id' => $storeItem->id,
                'slug' => $storeItem->slug,
                'title' => $storeItem->title,
                'summary' => $storeItem->summary,
                'description' => $storeItem->description,
                'sku' => $item?->sku,
                'category' => $item?->category === null ? null : [
                    'id' => $item->category->id,
                    'name' => $item->category->name,
                ],
                'unit' => $units[$storeItem->item_id] ?? null,
                'price' => $price,
                'secondary_price' => $price === null || $secondary === null
                    ? null
                    : $this->secondaryPrice($settings, $price, $secondary),
                'availability' => $this->availabilityOf($settings, $item, $availability),
                'is_featured' => $storeItem->is_featured,
                'order' => $storeItem->order,
                'published_at' => $storeItem->published_at?->format('Y-m-d H:i:s'),
                'images' => $storeItem->activeImages
                    ->map(fn (StoreItemImage $image): array => [
                        'url' => $image->url(),
                        'alt' => $image->alt_text,
                        'width' => $image->width,
                        'height' => $image->height,
                    ])
                    ->values()
                    ->all(),
            ];
        })->values()->all();
    }

    /**
     * Precio de cada artículo en la lista dada, indexado por `item_id`. Sin
     * lista, o sin fila activa para el artículo, no hay precio y la tienda
     * muestra «Consultar».
     *
     * @param  array<int, string>  $itemIds
     * @return array<string, array{amount: string, currency: string}>
     */
    public function pricesFor(StoreSetting $settings, ?string $priceListId, array $itemIds): array
    {
        if ($priceListId === null || $itemIds === []) {
            return [];
        }

        return ItemPrice::query()
            ->where('company_id', $settings->company_id)
            ->where('price_list_id', $priceListId)
            ->where('status', 'active')
            ->whereIn('item_id', $itemIds)
            ->get()
            ->mapWithKeys(fn (ItemPrice $price): array => [
                $price->item_id => [
                    'amount' => number_format((float) $price->price, 2, '.', ''),
                    'currency' => $price->currency,
                ],
            ])
            ->all();
    }

    /**
     * Cantidad disponible por artículo: suma de `available_quantity` de las
     * existencias activas, acotada a la bodega de la tienda si hay una.
     *
     * @param  array<int, string>  $itemIds
     * @return array<string, float>
     */
    public function availabilityFor(StoreSetting $settings, array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        return ItemStock::query()
            ->selectRaw('item_id, SUM(available_quantity) AS available')
            ->where('company_id', $settings->company_id)
            ->where('status', 'active')
            ->whereIn('item_id', $itemIds)
            ->when($settings->warehouse_id, fn ($q) => $q->where('warehouse_id', $settings->warehouse_id))
            ->groupBy('item_id')
            ->get()
            ->mapWithKeys(fn (ItemStock $row): array => [
                (string) $row->item_id => round((float) $row->getAttribute('available'), 4),
            ])
            ->all();
    }

    /**
     * La tasa del día que la tienda publica en `/settings`: cuántas unidades
     * de la moneda secundaria vale una de la moneda de la lista de la tienda.
     *
     * @return array{currency: string, secondary_currency: string, exchange_rate: string}|null
     */
    public function todayRate(StoreSetting $settings): ?array
    {
        $secondary = $this->secondaryCurrency($settings);
        $currency = $this->storeCurrency($settings);

        if ($secondary === null || $currency === null) {
            return null;
        }

        $rate = $this->tryConvert($settings, 1.0, $currency, $secondary);

        if ($rate === null) {
            return null;
        }

        return [
            'currency' => $currency,
            'secondary_currency' => $secondary,
            'exchange_rate' => number_format($rate, 8, '.', ''),
        ];
    }

    /**
     * Moneda de los precios de la tienda: la de la primera fila de precio
     * activa de su lista, o la principal de la empresa si la lista está vacía.
     */
    public function storeCurrency(StoreSetting $settings): ?string
    {
        if ($settings->price_list_id === null) {
            return null;
        }

        $currency = ItemPrice::query()
            ->where('company_id', $settings->company_id)
            ->where('price_list_id', $settings->price_list_id)
            ->where('status', 'active')
            ->value('currency');

        return $currency ?? $this->configurations->execute((string) $settings->company_id)->base_currency;
    }

    /**
     * Moneda secundaria en la que se muestra el segundo precio, si la tienda
     * lo pide y la empresa la tiene.
     */
    private function secondaryCurrency(StoreSetting $settings): ?string
    {
        if ($settings->shows_secondary_currency !== 'yes') {
            return null;
        }

        $configuration = $this->configurations->execute((string) $settings->company_id);

        return $configuration->usesDualCurrency() ? $configuration->secondary_currency : null;
    }

    /**
     * @param  array{amount: string, currency: string}  $price
     * @return array{amount: string, currency: string, exchange_rate: string}|null
     */
    private function secondaryPrice(StoreSetting $settings, array $price, string $secondary): ?array
    {
        if ($price['currency'] === $secondary) {
            return null;
        }

        $rate = $this->tryConvert($settings, 1.0, $price['currency'], $secondary);

        if ($rate === null) {
            return null;
        }

        return [
            'amount' => number_format(round((float) $price['amount'] * $rate, 2), 2, '.', ''),
            'currency' => $secondary,
            'exchange_rate' => number_format($rate, 8, '.', ''),
        ];
    }

    /** Conversión a la fecha de hoy que no bloquea: sin tasa, sin segundo precio. */
    private function tryConvert(StoreSetting $settings, float $amount, string $from, string $to): ?float
    {
        $type = $this->configurations->execute((string) $settings->company_id)->rate_type;

        try {
            return $this->rates->convert($amount, $from, $to, (string) $settings->company_id, now()->toDateString(), $type);
        } catch (ExchangeRateNotFoundException) {
            return null;
        }
    }

    /**
     * @param  array<string, float>  $availability
     * @return array{in_stock: string, quantity?: string}
     */
    private function availabilityOf(StoreSetting $settings, ?Item $item, array $availability): array
    {
        if ($item !== null && ! $item->movesStock()) {
            return ['in_stock' => 'yes'];
        }

        $quantity = $availability[$item?->id] ?? 0.0;

        $result = ['in_stock' => $quantity > 0 ? 'yes' : 'no'];

        if ($settings->shows_stock === 'yes') {
            $result['quantity'] = number_format(max($quantity, 0), 4, '.', '');
        }

        return $result;
    }

    /**
     * Unidad base de cada artículo, indexada por `item_id`.
     *
     * @param  array<int, string>  $itemIds
     * @return array<string, array{id: string, name: string, abbreviation: ?string}>
     */
    private function baseUnitsFor(string $companyId, array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        return ItemUnit::query()
            ->with('measurementUnit')
            ->where('company_id', $companyId)
            ->where('is_base', 'yes')
            ->whereIn('item_id', $itemIds)
            ->get()
            ->mapWithKeys(fn (ItemUnit $unit): array => [
                (string) $unit->item_id => [
                    'id' => (string) $unit->measurement_unit_id,
                    'name' => $unit->measurementUnit?->name ?? '',
                    'abbreviation' => $unit->measurementUnit?->abbreviation,
                ],
            ])
            ->all();
    }
}
