<?php

declare(strict_types=1);

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Commands\HoldPosSaleCommand;
use App\Modules\Pos\Requests\HoldPosSaleRequest;
use App\Modules\Pos\Resources\PosSaleResource;
use App\Modules\Pos\Services\PosSaleHoldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosSaleHoldPostController extends Controller
{
    public function __construct(
        private readonly PosSaleHoldService $holdService,
    ) {}

    public function hold(HoldPosSaleRequest $request): JsonResponse
    {
        $draft = $this->holdService->hold(
            HoldPosSaleCommand::fromRequest($request, session('current_company_id'))
        );

        $resource = (new PosSaleResource($draft))->resolve();

        return response()->json([
            'message' => 'Ticket puesto en espera exitosamente.',
            'sale' => $resource,
            'heldSale' => $resource,
        ], 201);
    }

    public function list(Request $request, string $company, string $sessionId): JsonResponse
    {
        $held = $this->holdService->listHeld($sessionId);

        return response()->json([
            'data' => PosSaleResource::collection($held),
        ]);
    }

    public function discard(Request $request, string $company, string $id): JsonResponse
    {
        $this->holdService->discard($id, session('current_company_id'));

        return response()->json([
            'message' => 'Ticket en espera descartado.',
        ]);
    }
}
