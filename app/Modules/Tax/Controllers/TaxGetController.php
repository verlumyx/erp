<?php

declare(strict_types=1);

namespace App\Modules\Tax\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tax\Commands\SearchTaxCommand;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Resources\TaxResource;
use App\Modules\Tax\Services\TaxFindService;
use App\Modules\Tax\Services\TaxSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaxGetController extends Controller
{
    public function __construct(
        private readonly TaxSearchService $searchService,
        private readonly TaxFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('taxes.list') ?? false, 403);

        $command = new SearchTaxCommand(
            filters: $request->only(['name', 'code', 'has_withholding', 'status']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('taxes/index', [
            'taxes' => array_map(
                fn (Tax $tax): array => (new TaxResource($tax))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['name', 'code', 'has_withholding', 'status', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('taxes.create') ?? false, 403);

        return Inertia::render('taxes/create');
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('taxes.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('taxes/show', [
            'tax' => (new TaxResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('taxes.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('taxes/edit', [
            'tax' => (new TaxResource($model))->resolve(),
        ]);
    }
}
