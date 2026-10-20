<?php

declare(strict_types=1);

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Resources\PosSaleResource;
use App\Modules\Pos\Services\PosSaleCancelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosSaleCancelPostController extends Controller
{
    public function __construct(
        private readonly PosSaleCancelService $cancelService,
    ) {}

    public function __invoke(Request $request, string $company, string $id): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('pos-sales.cancel') ?? false, 403);

        $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'cancellation_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $reason = $request->input('cancellation_reason') ?? $request->input('reason');

        $sale = $this->cancelService->execute(
            saleId: $id,
            cancelledBy: $request->user()->id,
            reason: $reason,
            companyId: session('current_company_id')
        );

        return response()->json([
            'message' => "Venta {$sale->code} anulada exitosamente.",
            'sale' => (new PosSaleResource($sale))->resolve(),
        ]);
    }
}
