<?php

declare(strict_types=1);

namespace App\Modules\Menu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Menu\Commands\SearchMenuCommand;
use App\Modules\Menu\Services\MenuFindService;
use App\Modules\Menu\Services\MenuSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MenuGetController extends Controller
{
    public function __construct(
        private readonly MenuSearchService $searchService,
        private readonly MenuFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        $command = new SearchMenuCommand(
            filters: $request->only(['permission']),
            limit: $request->integer('limit', 100),
            offset: $request->integer('offset', 0),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('Menus/index', [
            'items' => $result['data'],
            'total' => $result['total'],
            'filters' => $request->only(['permission', 'limit', 'offset']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Menus/create');
    }

    public function show(string $company, string $id): Response
    {
        $model = $this->findService->execute($id);

        return Inertia::render('Menus/show', [
            'item' => $model,
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        $model = $this->findService->execute($id);

        return Inertia::render('Menus/edit', [
            'item' => $model,
        ]);
    }
}
