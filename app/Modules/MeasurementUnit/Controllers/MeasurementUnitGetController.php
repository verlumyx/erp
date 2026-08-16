<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MeasurementUnit\Commands\SearchMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\MeasurementUnit\Resources\MeasurementUnitResource;
use App\Modules\MeasurementUnit\Services\MeasurementUnitFindService;
use App\Modules\MeasurementUnit\Services\MeasurementUnitSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MeasurementUnitGetController extends Controller
{
    public function __construct(
        private readonly MeasurementUnitSearchService $searchService,
        private readonly MeasurementUnitFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('measurement-units.list') ?? false, 403);

        $command = new SearchMeasurementUnitCommand(
            filters: $request->only(['name', 'abbreviation', 'code', 'status']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('measurement-units/index', [
            'measurementUnits' => array_map(
                fn (MeasurementUnit $unit): array => (new MeasurementUnitResource($unit))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['name', 'abbreviation', 'code', 'status', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('measurement-units.create') ?? false, 403);

        return Inertia::render('measurement-units/create');
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('measurement-units.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('measurement-units/show', [
            'measurementUnit' => (new MeasurementUnitResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('measurement-units.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('measurement-units/edit', [
            'measurementUnit' => (new MeasurementUnitResource($model))->resolve(),
        ]);
    }
}
