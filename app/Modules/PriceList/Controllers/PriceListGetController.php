<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PriceList\Commands\SearchPriceListCommand;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\PriceList\Resources\PriceListResource;
use App\Modules\PriceList\Services\PriceListFindService;
use App\Modules\PriceList\Services\PriceListSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PriceListGetController extends Controller
{
    public function __construct(
        private readonly PriceListSearchService $searchService,
        private readonly PriceListFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('price-lists.list') ?? false, 403);

        $command = new SearchPriceListCommand(
            filters: $request->only(['name', 'description', 'code', 'status']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('price-lists/index', [
            'priceLists' => array_map(
                fn (PriceList $priceList): array => (new PriceListResource($priceList))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['name', 'description', 'code', 'status', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('price-lists.create') ?? false, 403);

        return Inertia::render('price-lists/create');
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('price-lists.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('price-lists/show', [
            'priceList' => (new PriceListResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('price-lists.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('price-lists/edit', [
            'priceList' => (new PriceListResource($model))->resolve(),
        ]);
    }
}
