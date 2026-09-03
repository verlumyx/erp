<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Item\Models\Item;
use App\Modules\Store\Commands\SearchStoreItemCommand;
use App\Modules\Store\Models\StoreItem;
use App\Modules\Store\Resources\StoreItemPublishableOptionResource;
use App\Modules\Store\Resources\StoreItemResource;
use App\Modules\Store\Services\StoreItemFindService;
use App\Modules\Store\Services\StoreItemPublishableSearchService;
use App\Modules\Store\Services\StoreItemSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreItemGetController extends Controller
{
    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    public function __construct(
        private readonly StoreItemSearchService $searchService,
        private readonly StoreItemFindService $findService,
        private readonly StoreItemPublishableSearchService $publishableSearchService,
    ) {}

    public function index(Request $request, string $company): Response
    {
        abort_unless($request->user()?->hasPermission('store-items.list') ?? false, 403);

        $command = new SearchStoreItemCommand(
            filters: $request->only(['q', 'is_featured', 'status', 'category_id']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: $company,
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('store/items/index', [
            'store_items' => array_map(
                fn (StoreItem $storeItem): array => (new StoreItemResource($storeItem))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['q', 'is_featured', 'status', 'category_id', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('store-items.create') ?? false, 403);

        return Inertia::render('store/items/create');
    }

    /**
     * Opciones del select remoto de artículos publicables. Devuelve JSON,
     * no Inertia: la consume `Select2Ajax` por `fetch`.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('store-items.create') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $result = $this->publishableSearchService->execute(
            $company,
            $request->string('q')->toString(),
            $perPage,
            $offset,
        );

        return response()->json([
            'data' => array_map(
                fn (Item $item): array => (new StoreItemPublishableOptionResource($item))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $offset + $perPage,
        ]);
    }

    public function show(Request $request, string $company, string $id): Response
    {
        abort_unless($request->user()?->hasPermission('store-items.list') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('store/items/show', [
            'store_item' => (new StoreItemResource($model))->resolve(),
        ]);
    }

    public function edit(Request $request, string $company, string $id): Response
    {
        abort_unless($request->user()?->hasPermission('store-items.edit') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('store/items/edit', [
            'store_item' => (new StoreItemResource($model))->resolve(),
        ]);
    }
}
