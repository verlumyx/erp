<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SupplierPayment\Commands\SearchSupplierPaymentCommand;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Resources\SupplierPaymentResource;
use App\Modules\SupplierPayment\Services\SupplierPaymentFindService;
use App\Modules\SupplierPayment\Services\SupplierPaymentSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierPaymentGetController extends Controller
{
    /** Claves de filtro aceptadas por el listado. */
    private const FILTERS = [
        'code', 'supplier_id', 'reference', 'payment_method',
        'origin_type', 'status', 'date_from', 'date_to',
    ];

    public function __construct(
        private readonly SupplierPaymentSearchService $searchService,
        private readonly SupplierPaymentFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('supplier-payments.list') ?? false, 403);

        $command = new SearchSupplierPaymentCommand(
            filters: $request->only(self::FILTERS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('supplier-payments/index', [
            'supplierPayments' => array_map(
                fn (SupplierPayment $payment): array => (new SupplierPaymentResource($payment))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([...self::FILTERS, 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('supplier-payments.create') ?? false, 403);

        return Inertia::render('supplier-payments/create');
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('supplier-payments.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('supplier-payments/show', [
            'supplierPayment' => (new SupplierPaymentResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('supplier-payments.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('supplier-payments/edit', [
            'supplierPayment' => (new SupplierPaymentResource($model))->resolve(),
        ]);
    }
}
