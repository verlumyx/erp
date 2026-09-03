<?php

declare(strict_types=1);

namespace App\Modules\Role\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Permission\Repositories\Contracts\PermissionRepositoryInterface;
use App\Modules\Role\Commands\SearchRoleCommand;
use App\Modules\Role\Models\Role;
use App\Modules\Role\Services\RoleFindService;
use App\Modules\Role\Services\RoleSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleGetController extends Controller
{
    public function __construct(
        private readonly RoleSearchService $searchService,
        private readonly RoleFindService $findService,
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {}

    public function index(Request $request, string $company): Response
    {
        abort_unless($request->user()?->hasPermission('roles.list') ?? false, 403);

        $command = new SearchRoleCommand(
            filters: $request->only(['name', 'status', 'description']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: $company,
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('roles/index', [
            'roles' => $result['data'],
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['name', 'status', 'description', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('roles.create') ?? false, 403);

        return Inertia::render('roles/create', [
            'permissions' => $this->buildPermissionsPayload(),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('roles.show') ?? false, 403);

        $model = $this->findService->execute($id);

        return Inertia::render('roles/show', [
            'role' => $this->formatRole($model),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('roles.update') ?? false, 403);

        $model = $this->findService->execute($id);

        return Inertia::render('roles/edit', [
            'role' => $this->formatRole($model),
            'permissions' => $this->buildPermissionsPayload(),
        ]);
    }

    /** @return array<string, mixed> */
    private function formatRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'status' => $role->status,
            'description' => $role->description,
            'permission_type' => $role->permission_type,
            'permissions' => $role->permissions()->pluck('permission')->toArray(),
            'created_at' => $role->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $role->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /** @return array<string, mixed> */
    /**
     * Los permisos de los menús que la empresa no ve no se ofrecen: un rol no
     * puede dar acceso a algo que la empresa no tiene.
     */
    private function buildPermissionsPayload(): array
    {
        $companyId = session('current_company_id');
        $companyId = is_string($companyId) ? $companyId : null;

        return [
            'modules' => $this->permissionRepository->getAllGroupedByModule($companyId),
            'all' => $this->permissionRepository->getAllPermissionsFlat($companyId),
        ];
    }
}
