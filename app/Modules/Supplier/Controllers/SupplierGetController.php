<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Supplier\Commands\SearchSupplierCommand;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Resources\SupplierResource;
use App\Modules\Supplier\Services\SupplierFindService;
use App\Modules\Supplier\Services\SupplierFormOptionsService;
use App\Modules\Supplier\Services\SupplierSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierGetController extends Controller
{
    public function __construct(
        private readonly SupplierSearchService $searchService,
        private readonly SupplierFindService $findService,
        private readonly SupplierFormOptionsService $formOptionsService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('suppliers.list') ?? false, 403);

        $command = new SearchSupplierCommand(
            filters: $request->only([
                'name', 'code', 'document_number', 'document_type', 'email', 'supplier_type_id', 'status',
            ]),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('suppliers/index', [
            'suppliers' => array_map(
                fn (Supplier $supplier): array => (new SupplierResource($supplier))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([
                'name', 'code', 'document_number', 'document_type', 'email', 'supplier_type_id', 'status',
                'limit', 'offset',
            ]),
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('suppliers.create') ?? false, 403);

        return Inertia::render('suppliers/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('suppliers.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('suppliers/show', [
            'supplier' => (new SupplierResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('suppliers.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('suppliers/edit', [
            'supplier' => (new SupplierResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
