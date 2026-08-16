<?php

declare(strict_types=1);

namespace App\Modules\Category\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Category\Commands\SearchCategoryCommand;
use App\Modules\Category\Models\Category;
use App\Modules\Category\Resources\CategoryResource;
use App\Modules\Category\Services\CategoryFindService;
use App\Modules\Category\Services\CategorySearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryGetController extends Controller
{
    public function __construct(
        private readonly CategorySearchService $searchService,
        private readonly CategoryFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('categories.list') ?? false, 403);

        $command = new SearchCategoryCommand(
            filters: $request->only(['name', 'description', 'code', 'status']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('categories/index', [
            'categories' => array_map(
                fn (Category $category): array => (new CategoryResource($category))->resolve(),
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
        abort_unless($request->user()?->hasPermission('categories.create') ?? false, 403);

        return Inertia::render('categories/create');
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('categories.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('categories/show', [
            'category' => $model,
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('categories.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('categories/edit', [
            'category' => $model,
        ]);
    }
}
