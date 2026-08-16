<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SupplierType\Commands\SearchSupplierTypeCommand;
use App\Modules\SupplierType\Models\SupplierType;
use App\Modules\SupplierType\Resources\SupplierTypeResource;
use App\Modules\SupplierType\Services\SupplierTypeFindService;
use App\Modules\SupplierType\Services\SupplierTypeSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierTypeGetController extends Controller
{
    public function __construct(
        private readonly SupplierTypeSearchService $searchService,
        private readonly SupplierTypeFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('supplier-types.list') ?? false, 403);

        $command = new SearchSupplierTypeCommand(
            filters: $request->only(['name', 'code', 'status']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('supplier-types/index', [
            'supplierTypes' => array_map(
                fn (SupplierType $supplierType): array => (new SupplierTypeResource($supplierType))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['name', 'code', 'status', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('supplier-types.create') ?? false, 403);

        return Inertia::render('supplier-types/create');
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('supplier-types.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('supplier-types/show', [
            'supplierType' => (new SupplierTypeResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('supplier-types.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('supplier-types/edit', [
            'supplierType' => (new SupplierTypeResource($model))->resolve(),
        ]);
    }
}
