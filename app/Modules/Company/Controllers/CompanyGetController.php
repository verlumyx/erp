<?php

declare(strict_types=1);

namespace App\Modules\Company\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Commands\SearchCompanyCommand;
use App\Modules\Company\Services\CompanyFindService;
use App\Modules\Company\Services\CompanyMenusTreeService;
use App\Modules\Company\Services\CompanySearchService;
use App\Modules\Shared\Repositories\Contracts\CompanyDisabledMenuRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyGetController extends Controller
{
    public function __construct(
        private readonly CompanySearchService $searchService,
        private readonly CompanyFindService $findService,
        private readonly CompanyMenusTreeService $menusTreeService,
        private readonly CompanyDisabledMenuRepositoryInterface $disabledMenus,
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

    /** Qué menús ve esta empresa: el árbol completo y los que hoy tiene deshabilitados. */
    public function menus(string $company, string $id): Response
    {
        abort_unless(request()->user()?->is_system_owner ?? false, 403);

        $model = $this->findService->execute($id);

        return Inertia::render('companies/menus', [
            'company' => $model,
            /*
             * `menuTree` y no `menus`: ese nombre ya lo ocupa la prop
             * compartida con la que se dibuja el sidebar, y una prop de página
             * la tapa. Como las dos tienen la misma forma, el sidebar usaba
             * este árbol sin enterarse y sus enlaces salían sin el prefijo de
             * la empresa: navegar a otro módulo desde aquí daba 404.
             */
            'menuTree' => $this->menusTreeService->execute(),
            'disabled_menus' => $this->disabledMenus->disabledMenuIds($model->id),
        ]);
    }
}
