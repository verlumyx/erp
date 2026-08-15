<?php

declare(strict_types=1);

namespace App\Modules\Permission\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Permission\Commands\SearchPermissionCommand;
use App\Modules\Permission\Services\PermissionFindService;
use App\Modules\Permission\Services\PermissionSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PermissionGetController extends Controller
{
    public function __construct(
        private readonly PermissionSearchService $searchService,
        private readonly PermissionFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        $command = new SearchPermissionCommand(
            filters: $request->only(['label', 'module_id', 'is_active']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('Permissions/index', [
            'items' => $result['data'],
            'total' => $result['total'],
            'filters' => $request->only(['label', 'module_id', 'is_active', 'limit', 'offset']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Permissions/create');
    }

    public function show(string $company, string $id): Response
    {
        $model = $this->findService->execute($id);

        return Inertia::render('Permissions/show', [
            'item' => $model,
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        $model = $this->findService->execute($id);

        return Inertia::render('Permissions/edit', [
            'item' => $model,
        ]);
    }
}
