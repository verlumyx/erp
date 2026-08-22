<?php

declare(strict_types=1);

namespace App\Modules\Item\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Item\Commands\SearchItemCommand;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Resources\ItemOptionResource;
use App\Modules\Item\Resources\ItemResource;
use App\Modules\Item\Services\ItemFindService;
use App\Modules\Item\Services\ItemFormOptionsService;
use App\Modules\Item\Services\ItemOptionSearchService;
use App\Modules\Item\Services\ItemSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ItemGetController extends Controller
{
    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    public function __construct(
        private readonly ItemSearchService $searchService,
        private readonly ItemFindService $findService,
        private readonly ItemFormOptionsService $formOptionsService,
        private readonly ItemOptionSearchService $optionSearchService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('items.list') ?? false, 403);

        $command = new SearchItemCommand(
            filters: $request->only(['name', 'sku', 'barcode', 'code', 'type', 'category_id', 'status']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('items/index', [
            'items' => array_map(
                fn (Item $item): array => (new ItemResource($item))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([
                'name', 'sku', 'barcode', 'code', 'type', 'category_id', 'status', 'limit', 'offset',
            ]),
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('items.create') ?? false, 403);

        return Inertia::render('items/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    /**
     * Página de opciones para el select remoto de artículos.
     *
     * Devuelve JSON, no Inertia: la consume `Select2Ajax` por `fetch`. El
     * catálogo de artículos es demasiado grande para viajar entero en las props
     * de cada formulario que lo necesita.
     *
     * Se llama `lookup` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('items.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $ids = $request->string('ids')->toString();

        $command = new SearchItemCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $ids,
                'type' => $request->string('type')->toString(),
                'category_id' => $request->string('category_id')->toString(),
                'is_sellable' => $request->string('is_sellable')->toString(),
                'is_purchasable' => $request->string('is_purchasable')->toString(),
                /*
                 * Buscar ofrece solo artículos activos; hidratar lo ya elegido
                 * no filtra por estado: un artículo desactivado después sigue
                 * estando en el documento que se está editando.
                 */
                'status' => $ids === ''
                    ? $request->string('status', 'active')->toString()
                    : $request->string('status')->toString(),
            ],
            limit: $perPage,
            offset: ($page - 1) * $perPage,
            companyId: $company,
        );

        $result = $this->optionSearchService->execute($command);

        return response()->json([
            'data' => array_map(
                fn (Item $item): array => (new ItemOptionResource($item))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('items.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('items/show', [
            'item' => (new ItemResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('items.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('items/edit', [
            'item' => (new ItemResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
