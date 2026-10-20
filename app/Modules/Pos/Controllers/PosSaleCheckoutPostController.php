<?php

declare(strict_types=1);

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Commands\PosCheckoutCommand;
use App\Modules\Pos\Requests\PosCheckoutRequest;
use App\Modules\Pos\Resources\PosSaleResource;
use App\Modules\Pos\Services\PosSaleCheckoutService;
use Illuminate\Http\JsonResponse;

class PosSaleCheckoutPostController extends Controller
{
    public function __construct(
        private readonly PosSaleCheckoutService $checkoutService,
    ) {}

    public function __invoke(PosCheckoutRequest $request): JsonResponse
    {
        $sale = $this->checkoutService->execute(
            PosCheckoutCommand::fromRequest($request, session('current_company_id'))
        );

        return response()->json([
            'message' => 'Venta completada exitosamente.',
            'sale' => (new PosSaleResource($sale))->resolve(),
        ], 201);
    }
}
