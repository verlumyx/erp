<?php

declare(strict_types=1);

namespace App\Modules\Item\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Item\Commands\SearchItemCommand;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Resources\ItemResource;
use App\Modules\Item\Services\ItemFindService;
use App\Modules\Item\Services\ItemFormOptionsService;
use App\Modules\Item\Services\ItemSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ItemGetController extends Controller
{
    public function __construct(
        private readonly ItemSearchService $searchService,
        private readonly ItemFindService $findService,
        private readonly ItemFormOptionsService $formOptionsService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('items.list') ?? false, 403);

        $command = new SearchItemCommand(
            filters: $request->only(['name', 'sku', 'barcode', 'code', 'type', 'category_id', 'status']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('items/index', [
            'items' => array_map(
                fn (Item $item): array => (new ItemResource($item))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([
                'name', 'sku', 'barcode', 'code', 'type', 'category_id', 'status', 'limit', 'offset',
            ]),
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('items.create') ?? false, 403);

        return Inertia::render('items/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('items.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('items/show', [
            'item' => (new ItemResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('items.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('items/edit', [
            'item' => (new ItemResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
