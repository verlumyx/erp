<?php

declare(strict_types=1);

namespace App\Modules\Refund\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Refund\Commands\SearchRefundCommand;
use App\Modules\Refund\Resources\RefundResource;
use App\Modules\Refund\Services\RefundFindService;
use App\Modules\Refund\Services\RefundSearchService;
use App\Modules\Sale\Models\Sale;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RefundGetController extends Controller
{
    public function __construct(
        private readonly RefundSearchService $searchService,
        private readonly RefundFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('refunds.list') ?? false, 403);

        $companyId = session('current_company_id');

        $filters = $request->only(['q', 'status', 'sale_id']);

        $command = new SearchRefundCommand(
            filters: $filters,
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: $companyId,
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('refunds/index', [
            'refunds' => RefundResource::collection($result['data'])->resolve(),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => array_merge($filters, $request->only(['limit', 'offset'])),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('refunds.create') ?? false, 403);

        $companyId = session('current_company_id');

        return Inertia::render('refunds/create', [
            'sales' => $this->refundableSales($companyId),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('refunds.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('refunds/show', [
            'refund' => (new RefundResource($model))->resolve(),
        ]);
    }

    /**
     * Ventas de la compañía candidatas a reembolso (activas o canceladas), para el
     * selector del formulario de creación manual.
     *
     * @return array<int, array<string, mixed>>
     */
    private function refundableSales(?string $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return Sale::query()
            ->with('client:id,name')
            ->where('company_id', $companyId)
            ->whereIn('status', [Sale::STATUS_ACTIVE, Sale::STATUS_CANCELLED])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'code', 'client_id', 'price', 'status'])
            ->map(fn (Sale $sale): array => [
                'id' => $sale->id,
                'code' => $sale->code,
                'client_name' => $sale->client?->name,
                'price' => $sale->price,
                'status' => $sale->status,
            ])
            ->all();
    }
}
