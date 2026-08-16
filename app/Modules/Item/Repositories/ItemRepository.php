<?php

declare(strict_types=1);

namespace App\Modules\Item\Repositories;

use App\Modules\Item\Commands\CreateItemCommand;
use App\Modules\Item\Commands\ItemPriceData;
use App\Modules\Item\Commands\ItemUnitData;
use App\Modules\Item\Commands\SearchItemCommand;
use App\Modules\Item\Commands\UpdateItemCommand;
use App\Modules\Item\Commands\UpdateStatusItemCommand;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemPrice;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ItemRepository extends ItemFilters implements ItemRepositoryInterface
{
    public function create(CreateItemCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            $item = Item::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'sku' => $command->sku,
                'barcode' => $command->barcode,
                'name' => $command->name,
                'description' => $command->description,
                'type' => $command->type,
                'category_id' => $command->categoryId,
                'cost_method' => $command->costMethod,
                'standard_cost' => $command->standardCost,
                'average_cost' => 0,
                'min_price' => $command->minPrice,
                'is_purchasable' => $command->isPurchasable,
                'is_sellable' => $command->isSellable,
                'min_stock' => $command->minStock,
                'max_stock' => $command->maxStock,
                'reorder_quantity' => $command->reorderQuantity,
                'weight' => $command->weight,
                'volume' => $command->volume,
                'notes' => $command->notes,
                'status' => 'active',
                'created_by' => $command->createdBy,
            ]);

            $this->syncUnits($item, $command->units);
            $this->syncPrices($item, $command->prices);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Item
    {
        return Item::query()
            ->with(['units.measurementUnit', 'prices.priceList', 'category'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Item
    {
        return Item::query()
            ->with(['units.measurementUnit', 'prices.priceList', 'category'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(Item $model, UpdateItemCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            $model->update([
                'sku' => $command->sku,
                'barcode' => $command->barcode,
                'name' => $command->name,
                'description' => $command->description,
                'type' => $command->type,
                'category_id' => $command->categoryId,
                'cost_method' => $command->costMethod,
                'standard_cost' => $command->standardCost,
                'min_price' => $command->minPrice,
                'is_purchasable' => $command->isPurchasable,
                'is_sellable' => $command->isSellable,
                'min_stock' => $command->minStock,
                'max_stock' => $command->maxStock,
                'reorder_quantity' => $command->reorderQuantity,
                'weight' => $command->weight,
                'volume' => $command->volume,
                'notes' => $command->notes,
            ]);

            $this->syncUnits($model, $command->units);
            $this->syncPrices($model, $command->prices);
        });
    }

    public function updateStatus(Item $model, UpdateStatusItemCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /**
     * @return array{ data: Item[], total: int }
     */
    public function search(SearchItemCommand $command): array
    {
        $query = Item::query()
            ->with('category')
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderBy('name')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Alinea `app_item_units` con lo enviado desde la pantalla del artículo.
     *
     * Las filas se identifican por su unidad de medida. Las que dejan de venir
     * no se borran: se desactivan (política de no borrado).
     *
     * @param  array<int, ItemUnitData>  $units
     */
    private function syncUnits(Item $item, array $units): void
    {
        $existing = ItemUnit::query()
            ->where('item_id', $item->id)
            ->get()
            ->keyBy('measurement_unit_id');

        $keep = [];

        foreach ($units as $unit) {
            $keep[] = $unit->measurementUnitId;

            $attributes = [
                'company_id' => $item->company_id,
                'is_base' => $unit->isBase,
                'conversion_factor' => $unit->conversionFactor,
                'status' => $unit->status,
            ];

            if ($existing->has($unit->measurementUnitId)) {
                $existing->get($unit->measurementUnitId)->update($attributes);

                continue;
            }

            ItemUnit::create([
                ...$attributes,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->measurementUnitId,
            ]);
        }

        ItemUnit::query()
            ->where('item_id', $item->id)
            ->whereNotIn('measurement_unit_id', $keep === [] ? [''] : $keep)
            ->update(['status' => 'inactive']);
    }

    /**
     * Alinea `app_item_prices` con lo enviado desde la pantalla del artículo.
     *
     * Las filas se identifican por lista de precio, que es la clave única de la
     * tabla: un solo precio por lista. Las que dejan de venir se desactivan.
     *
     * @param  array<int, ItemPriceData>  $prices
     */
    private function syncPrices(Item $item, array $prices): void
    {
        $existing = ItemPrice::query()
            ->where('item_id', $item->id)
            ->get()
            ->keyBy(fn (ItemPrice $price): string => $price->price_list_id);

        $keep = [];

        foreach ($prices as $price) {
            $keep[] = $price->priceListId;

            $attributes = [
                'company_id' => $item->company_id,
                'price' => $price->price,
                'currency' => $price->currency,
                'status' => $price->status,
            ];

            if ($existing->has($price->priceListId)) {
                $existing->get($price->priceListId)->update($attributes);

                continue;
            }

            ItemPrice::create([
                ...$attributes,
                'item_id' => $item->id,
                'price_list_id' => $price->priceListId,
            ]);
        }

        $obsolete = $existing->reject(fn (ItemPrice $price, string $key): bool => in_array($key, $keep, true));

        if ($obsolete->isNotEmpty()) {
            ItemPrice::query()
                ->whereIn('id', $obsolete->pluck('id')->all())
                ->update(['status' => 'inactive']);
        }
    }

    /**
     * Generate the next sequential per-company code (ART000001, ART000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Item::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Item::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Item::CODE_PREFIX))) + 1
            : 1;

        return Item::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
