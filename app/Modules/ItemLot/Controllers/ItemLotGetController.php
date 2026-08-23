<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ItemLot\Commands\SearchItemLotCommand;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemLot\Resources\ItemLotOptionResource;
use App\Modules\ItemLot\Resources\ItemLotResource;
use App\Modules\ItemLot\Services\ItemLotFindService;
use App\Modules\ItemLot\Services\ItemLotOptionSearchService;
use App\Modules\ItemLot\Services\ItemLotSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ItemLotGetController extends Controller
{
    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    /** @var array<int, string> */
    private const FILTER_KEYS = ['code', 'lot_number', 'item_id', 'supplier_id', 'status', 'expires_before'];

    public function __construct(
        private readonly ItemLotSearchService $searchService,
        private readonly ItemLotFindService $findService,
        private readonly ItemLotOptionSearchService $optionSearchService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('item-lots.list') ?? false, 403);

        $command = new SearchItemLotCommand(
            filters: $request->only(self::FILTER_KEYS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('item-lots/index', [
            'lots' => array_map(
                fn (ItemLot $lot): array => (new ItemLotResource($lot))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([...self::FILTER_KEYS, 'limit', 'offset']),
        ]);
    }

    /**
     * Opciones para el select remoto de lotes.
     *
     * Se llama `lookup` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('item-lots.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $ids = $request->string('ids')->toString();

        $command = new SearchItemLotCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $ids,
                'item_id' => $request->string('item_id')->toString(),
                /*
                 * Buscar ofrece solo lotes disponibles; hidratar lo ya elegido
                 * no filtra por estado: un lote bloqueado o vencido después
                 * sigue estando en el documento que se está editando.
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
                fn (ItemLot $lot): array => (new ItemLotOptionResource($lot))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('item-lots.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('item-lots/show', [
            'lot' => (new ItemLotResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('item-lots.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('item-lots/edit', [
            'lot' => (new ItemLotResource($model))->resolve(),
        ]);
    }
}
