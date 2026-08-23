<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Supplier\Commands\SearchSupplierCommand;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Resources\SupplierOptionResource;
use App\Modules\Supplier\Resources\SupplierResource;
use App\Modules\Supplier\Services\SupplierFindService;
use App\Modules\Supplier\Services\SupplierFormOptionsService;
use App\Modules\Supplier\Services\SupplierSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierGetController extends Controller
{
    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

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

    /**
     * Página de opciones para el select remoto de proveedores.
     *
     * Devuelve JSON, no Inertia: la consume `Select2Ajax` por `fetch`. El
     * padrón de proveedores es demasiado grande para viajar entero en las props
     * de cada pantalla que lo necesita.
     *
     * Se llama `lookup` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('suppliers.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $ids = $request->string('ids')->toString();

        $command = new SearchSupplierCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $ids,
                /** Los pagos piden solo proveedores a los que se les debe algo. */
                'with_balance' => $request->string('with_balance')->toString(),
                /*
                 * Buscar ofrece solo proveedores activos; hidratar lo ya
                 * elegido no filtra por estado: un proveedor desactivado
                 * después sigue estando en la orden que se está editando.
                 */
                'status' => $ids === ''
                    ? $request->string('status', 'active')->toString()
                    : $request->string('status')->toString(),
            ],
            limit: $perPage,
            offset: ($page - 1) * $perPage,
            companyId: $company,
        );

        $result = $this->searchService->execute($command);

        return response()->json([
            'data' => array_map(
                fn (Supplier $supplier): array => (new SupplierOptionResource($supplier))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
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
