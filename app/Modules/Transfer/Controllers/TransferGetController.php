<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transfer\Commands\SearchTransferCommand;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Resources\TransferOptionResource;
use App\Modules\Transfer\Resources\TransferResource;
use App\Modules\Transfer\Services\TransferFindService;
use App\Modules\Transfer\Services\TransferFormOptionsService;
use App\Modules\Transfer\Services\TransferOptionSearchService;
use App\Modules\Transfer\Services\TransferSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransferGetController extends Controller
{
    /** Claves de filtro aceptadas por el listado. */
    private const FILTERS = [
        'code', 'origin_warehouse_id', 'destination_warehouse_id', 'warehouse_id',
        'driver_id', 'route_id', 'reason', 'transfer_status', 'status',
        'date_from', 'date_to',
    ];

    /** Tamaño de página del select remoto. */
    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    public function __construct(
        private readonly TransferSearchService $searchService,
        private readonly TransferFindService $findService,
        private readonly TransferFormOptionsService $formOptionsService,
        private readonly TransferOptionSearchService $optionSearchService,
    ) {}

    /**
     * Traslados como opciones de un select remoto, para el documento que
     * mañana necesite señalar uno —un ajuste que justifique un faltante, sin ir
     * más lejos— sin descargar el padrón entero en sus props.
     *
     * Se llama `lookup` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('transfers.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);

        $command = new SearchTransferCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $request->string('ids')->toString(),
                'warehouse_id' => $request->string('warehouse_id')->toString(),
                'transfer_status' => $request->string('transfer_status')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            limit: $perPage,
            offset: ($page - 1) * $perPage,
            companyId: $company,
        );

        $result = $this->optionSearchService->execute($command);

        return response()->json([
            'data' => array_map(
                fn (Transfer $transfer): array => (new TransferOptionResource($transfer))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
        ]);
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('transfers.list') ?? false, 403);

        $command = new SearchTransferCommand(
            filters: $request->only(self::FILTERS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('transfers/index', [
            'transfers' => array_map(
                fn (Transfer $transfer): array => (new TransferResource($transfer))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([...self::FILTERS, 'limit', 'offset']),
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('transfers.create') ?? false, 403);

        return Inertia::render('transfers/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('transfers.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('transfers/show', [
            'transfer' => (new TransferResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('transfers.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('transfers/edit', [
            'transfer' => (new TransferResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
