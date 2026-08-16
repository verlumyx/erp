<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Commands\SearchUserCommand;
use App\Modules\User\Models\User;
use App\Modules\User\Services\UserSearchService;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Resources\WarehouseResource;
use App\Modules\Warehouse\Services\WarehouseFindService;
use App\Modules\Warehouse\Services\WarehouseSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseGetController extends Controller
{
    /** Tope de usuarios cargados para el selector de encargado. */
    private const RESPONSIBLE_OPTIONS_LIMIT = 200;

    public function __construct(
        private readonly WarehouseSearchService $searchService,
        private readonly WarehouseFindService $findService,
        private readonly UserSearchService $userSearchService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('warehouses.list') ?? false, 403);

        $command = new SearchWarehouseCommand(
            filters: $request->only(['name', 'code', 'city', 'type', 'status']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('warehouses/index', [
            'warehouses' => array_map(
                fn (Warehouse $warehouse): array => (new WarehouseResource($warehouse))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['name', 'code', 'city', 'type', 'status', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('warehouses.create') ?? false, 403);

        return Inertia::render('warehouses/create', [
            'users' => $this->responsibleOptions(),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('warehouses.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('warehouses/show', [
            'warehouse' => (new WarehouseResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('warehouses.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('warehouses/edit', [
            'warehouse' => (new WarehouseResource($model))->resolve(),
            'users' => $this->responsibleOptions(),
        ]);
    }

    /**
     * Usuarios de la empresa activa que pueden quedar como encargados.
     *
     * @return array<int, array{id: string, name: string}>
     */
    private function responsibleOptions(): array
    {
        $result = $this->userSearchService->execute(new SearchUserCommand(
            filters: [],
            limit: self::RESPONSIBLE_OPTIONS_LIMIT,
            offset: 0,
            companyId: session('current_company_id'),
        ));

        return array_map(
            fn (User $user): array => ['id' => $user->id, 'name' => $user->name],
            $result['data'],
        );
    }
}
