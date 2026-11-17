<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vehicle\Commands\SearchVehicleCommand;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Vehicle\Resources\VehicleOptionResource;
use App\Modules\Vehicle\Resources\VehicleResource;
use App\Modules\Vehicle\Services\VehicleFindService;
use App\Modules\Vehicle\Services\VehicleOptionSearchService;
use App\Modules\Vehicle\Services\VehicleSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleGetController extends Controller
{
    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    public function __construct(
        private readonly VehicleSearchService $searchService,
        private readonly VehicleFindService $findService,
        private readonly VehicleOptionSearchService $optionSearchService,
    ) {}

    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('vehicles.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);

        $command = new SearchVehicleCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $request->string('ids')->toString(),
                'status' => $request->string('status')->toString(),
                'vehicle_type' => $request->string('vehicle_type')->toString(),
            ],
            limit: $perPage,
            offset: ($page - 1) * $perPage,
            companyId: $company,
        );

        $result = $this->optionSearchService->execute($command);

        return response()->json([
            'data' => array_map(
                fn (Vehicle $vehicle): array => (new VehicleOptionResource($vehicle))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
        ]);
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('vehicles.list') ?? false, 403);

        $command = new SearchVehicleCommand(
            filters: $request->only(['q', 'plate', 'name', 'brand', 'model', 'vehicle_type', 'status']),
            limit: $request->integer('limit', 10),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('vehicles/index', [
            'vehicles' => array_map(
                fn (Vehicle $vehicle): array => (new VehicleResource($vehicle))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['q', 'plate', 'name', 'brand', 'model', 'vehicle_type', 'status', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('vehicles.create') ?? false, 403);

        return Inertia::render('vehicles/create', [
            'vehicleTypes' => Vehicle::VEHICLE_TYPES,
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('vehicles.show') ?? false, 403);

        $vehicle = $this->findService->execute($id, $company);

        return Inertia::render('vehicles/show', [
            'vehicle' => (new VehicleResource($vehicle))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('vehicles.update') ?? false, 403);

        $vehicle = $this->findService->execute($id, $company);

        return Inertia::render('vehicles/edit', [
            'vehicle' => (new VehicleResource($vehicle))->resolve(),
            'vehicleTypes' => Vehicle::VEHICLE_TYPES,
        ]);
    }
}
