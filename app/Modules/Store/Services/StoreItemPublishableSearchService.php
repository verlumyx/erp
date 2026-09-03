<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Item\Models\Item;
use App\Modules\Store\Models\StoreItem;
use Illuminate\Database\Eloquent\Builder;

/**
 * Artículos que todavía se pueden publicar: vendibles, activos y sin
 * publicación en la empresa. Alimenta el select remoto de la pantalla de
 * crear publicación.
 *
 * Vive en este módulo y no en `Item` porque la regla «sin publicación» es
 * de la tienda: el catálogo de artículos no sabe que existe.
 */
class StoreItemPublishableSearchService
{
    /**
     * @return array{ data: Item[], total: int }
     */
    public function execute(string $companyId, string $q, int $limit, int $offset): array
    {
        $query = Item::query()
            ->with('category')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->where('is_sellable', 'yes')
            ->whereNotIn('id', StoreItem::query()
                ->select('item_id')
                ->where('company_id', $companyId))
            ->when($q !== '', fn (Builder $query): Builder => $query->where(function (Builder $inner) use ($q): void {
                $inner->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            }));

        $total = $query->count();

        $data = $query->orderBy('name')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }
}
