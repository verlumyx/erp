<?php

declare(strict_types=1);

namespace App\Modules\Store\Repositories;

use App\Modules\Category\Models\Category;
use App\Modules\Store\Commands\CreateStoreItemCommand;
use App\Modules\Store\Commands\SearchStoreItemCommand;
use App\Modules\Store\Commands\UpdateStatusStoreItemCommand;
use App\Modules\Store\Commands\UpdateStoreItemCommand;
use App\Modules\Store\Models\StoreItem;
use App\Modules\Store\Repositories\Contracts\StoreItemRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreItemRepository extends StoreItemFilters implements StoreItemRepositoryInterface
{
    /** Relaciones que necesitan la pantalla de detalle y el formulario de edición. */
    private const DETAIL_RELATIONS = ['item.category', 'images'];

    public function create(CreateStoreItemCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            StoreItem::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'item_id' => $command->itemId,
                'slug' => $this->uniqueSlug($command->companyId, $command->slug ?? (string) $command->title),
                'title' => $command->title,
                'summary' => $command->summary,
                'description' => $command->description,
                'is_featured' => $command->isFeatured,
                'order' => $command->order,
                /** Nace activa: la primera publicación es ahora. */
                'published_at' => now(),
                'status' => 'active',
                'created_by' => $command->createdBy,
            ]);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?StoreItem
    {
        return StoreItem::query()
            ->with(self::DETAIL_RELATIONS)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): StoreItem
    {
        return StoreItem::query()
            ->with(self::DETAIL_RELATIONS)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function findByItem(string $itemId, string $companyId): ?StoreItem
    {
        return StoreItem::query()
            ->where('company_id', $companyId)
            ->where('item_id', $itemId)
            ->first();
    }

    public function update(StoreItem $model, UpdateStoreItemCommand $command): void
    {
        $model->update([
            'slug' => $command->slug === null
                ? $model->slug
                : $this->uniqueSlug((string) $model->company_id, $command->slug, $model->id),
            'title' => $command->title,
            'summary' => $command->summary,
            'description' => $command->description,
            'is_featured' => $command->isFeatured,
            'order' => $command->order,
        ]);
    }

    public function updateStatus(StoreItem $model, UpdateStatusStoreItemCommand $command): void
    {
        $attributes = ['status' => $command->status];

        if ($command->status === 'active' && $model->published_at === null) {
            $attributes['published_at'] = now();
        }

        $model->update($attributes);
    }

    /**
     * @return array{ data: StoreItem[], total: int }
     */
    public function search(SearchStoreItemCommand $command): array
    {
        $query = StoreItem::query()
            ->with(['item.category', 'activeImages'])
            ->when($command->companyId, fn ($q) => $q->where('app_store_items.company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderBy('app_store_items.order')
            ->orderBy('app_store_items.title')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Ordenar por precio se resuelve en la base con un join a la lista
     * efectiva: paginar primero y ordenar después dejaría la página mal
     * armada. Sin lista, `price` cae al orden de presentación.
     *
     * @return array{ data: StoreItem[], total: int }
     */
    public function searchVisible(SearchStoreItemCommand $command, ?string $priceListId = null): array
    {
        $query = StoreItem::query()
            ->select('app_store_items.*')
            ->with(['item.category', 'activeImages'])
            ->where('app_store_items.company_id', $command->companyId)
            ->visibleInStore();

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $this->applyPublicSort($query, $command->sort, $priceListId);

        $data = $query->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    public function findVisibleBySlug(string $companyId, string $slug): ?StoreItem
    {
        return StoreItem::query()
            ->with(['item.category', 'activeImages'])
            ->where('app_store_items.company_id', $companyId)
            ->where('app_store_items.slug', $slug)
            ->visibleInStore()
            ->first();
    }

    /**
     * @return array<int, array{id: string, name: string, count: int}>
     */
    public function visibleCategories(string $companyId): array
    {
        $counts = StoreItem::query()
            ->join('app_items', 'app_items.id', '=', 'app_store_items.item_id')
            ->where('app_store_items.company_id', $companyId)
            ->visibleInStore()
            ->whereNotNull('app_items.category_id')
            ->groupBy('app_items.category_id')
            ->selectRaw('app_items.category_id AS category_id, COUNT(*) AS total')
            ->pluck('total', 'category_id');

        if ($counts->isEmpty()) {
            return [];
        }

        return Category::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereIn('id', $counts->keys()->all())
            ->orderBy('order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => (string) $category->id,
                'name' => (string) $category->name,
                'count' => (int) $counts->get($category->id, 0),
            ])
            ->values()
            ->all();
    }

    private function applyPublicSort(Builder $query, string $sort, ?string $priceListId): void
    {
        match (true) {
            $sort === 'name' => $query->orderBy('app_store_items.title'),
            $sort === 'newest' => $query->orderByDesc('app_store_items.published_at')->orderByDesc('app_store_items.created_at'),
            $sort === 'price' && $priceListId !== null => $query
                ->leftJoin('app_item_prices', function ($join) use ($priceListId): void {
                    $join->on('app_item_prices.item_id', '=', 'app_store_items.item_id')
                        ->where('app_item_prices.price_list_id', '=', $priceListId)
                        ->where('app_item_prices.status', '=', 'active');
                })
                ->orderByRaw('app_item_prices.price IS NULL')
                ->orderBy('app_item_prices.price')
                ->orderBy('app_store_items.title'),
            default => $query->orderBy('app_store_items.order')->orderBy('app_store_items.title'),
        };
    }

    /**
     * Slug único por empresa: minúsculas, `a-z0-9-`. Al chocar se agrega un
     * sufijo numérico (`-2`, `-3`). Al editar, la propia fila no cuenta como
     * choque.
     */
    private function uniqueSlug(string $companyId, string $source, ?string $ignoreId = null): string
    {
        $base = Str::slug($source);

        if ($base === '') {
            $base = 'producto';
        }

        $base = Str::limit($base, 150, '');
        $candidate = $base;
        $suffix = 2;

        while ($this->slugExists($companyId, $candidate, $ignoreId)) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function slugExists(string $companyId, string $slug, ?string $ignoreId): bool
    {
        return StoreItem::query()
            ->where('company_id', $companyId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * Generate the next sequential per-company code (PUB000001, PUB000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = StoreItem::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', StoreItem::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(StoreItem::CODE_PREFIX))) + 1
            : 1;

        return StoreItem::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
