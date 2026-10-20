<?php

declare(strict_types=1);

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Commands\CreatePosCashMovementCommand;
use App\Modules\Pos\Requests\CreatePosCashMovementRequest;
use App\Modules\Pos\Services\PosCashMovementService;
use Illuminate\Http\JsonResponse;

class PosCashMovementPostController extends Controller
{
    public function __construct(
        private readonly PosCashMovementService $service,
    ) {}

    public function __invoke(CreatePosCashMovementRequest $request): JsonResponse
    {
        $movement = $this->service->execute(
            CreatePosCashMovementCommand::fromRequest($request, session('current_company_id'))
        );

        $typeLabel = $movement->movement_type === 'in' ? 'Entrada' : 'Retiro / Sangría';

        return response()->json([
            'message' => "{$typeLabel} de efectivo registrada correctamente.",
            'movement' => [
                'id' => $movement->id,
                'movement_type' => $movement->movement_type,
                'concept' => $movement->concept,
                'currency' => $movement->currency,
                'amount' => (float) $movement->amount,
                'reason' => $movement->reason,
                'created_at' => $movement->created_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
