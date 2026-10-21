<?php

declare(strict_types=1);

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Client\Models\Client;
use App\Modules\Pos\Commands\SearchPosRegisterCommand;
use App\Modules\Pos\Repositories\Contracts\PosRegisterRepositoryInterface;
use App\Modules\Pos\Resources\PosRegisterResource;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosRegisterGetController extends Controller
{
    public function __construct(
        private readonly PosRegisterRepositoryInterface $repository,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('pos-registers.list') ?? false, 403);

        $companyId = session('current_company_id');
        $command = new SearchPosRegisterCommand(
            companyId: $companyId,
            filters: $request->only(['status', 'warehouse_id']),
            search: $request->string('search')->toString() ?: null,
            sortBy: $request->string('sort_by', 'code')->toString(),
            sortDirection: $request->string('sort_direction', 'desc')->toString(),
            perPage: $request->integer('per_page', 10),
            page: $request->integer('page', 1),
        );

        $paginator = $this->repository->paginate($command);

        return Inertia::render('pos/registers/index', [
            'registers' => PosRegisterResource::collection($paginator),
            'filters' => [
                'search' => $command->search,
                'status' => $command->filters['status'] ?? null,
                'warehouse_id' => $command->filters['warehouse_id'] ?? null,
            ],
            'warehouses' => Warehouse::where('company_id', $companyId)->where('status', 'active')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('pos-registers.create') ?? false, 403);

        $companyId = session('current_company_id');

        return Inertia::render('pos/registers/create', [
            'warehouses' => Warehouse::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'priceLists' => PriceList::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'clients' => Client::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->limit(50)->get(['id', 'name', 'document_type', 'document_number']),
        ]);
    }

    public function edit(Request $request, string $company, string $id): Response
    {
        abort_unless($request->user()?->hasPermission('pos-registers.edit') ?? false, 403);

        $companyId = session('current_company_id');
        $register = $this->repository->findOrFail($id, $companyId);

        return Inertia::render('pos/registers/edit', [
            'register' => (new PosRegisterResource($register))->resolve(),
            'warehouses' => Warehouse::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'priceLists' => PriceList::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'clients' => Client::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->limit(50)->get(['id', 'name', 'document_type', 'document_number']),
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $companyId = session('current_company_id');
        $available = $this->repository->listAvailable($companyId);

        return response()->json([
            'data' => PosRegisterResource::collection($available),
        ]);
    }
}
