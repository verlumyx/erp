<?php

declare(strict_types=1);

namespace App\Modules\Company\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Commands\SearchCompanyCommand;
use App\Modules\Company\Services\CompanyFindService;
use App\Modules\Company\Services\CompanySearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyGetController extends Controller
{
    public function __construct(
        private readonly CompanySearchService $searchService,
        private readonly CompanyFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->is_system_owner ?? false, 403);

        $command = new SearchCompanyCommand(
            filters: $request->only(['name', 'status']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('companies/index', [
            'companies' => $result['data'],
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['name', 'status', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->is_system_owner ?? false, 403);

        return Inertia::render('companies/create');
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->is_system_owner ?? false, 403);

        $model = $this->findService->execute($id);

        return Inertia::render('companies/show', [
            'company' => $model,
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->is_system_owner ?? false, 403);

        $model = $this->findService->execute($id);

        return Inertia::render('companies/edit', [
            'company' => $model,
        ]);
    }
}
