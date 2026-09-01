<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dispatch\Commands\RegisterDispatchDeliveryCommand;
use App\Modules\Dispatch\Requests\RegisterDispatchDeliveryRequest;
use App\Modules\Dispatch\Services\DispatchDeliveryService;
use Illuminate\Http\RedirectResponse;

/**
 * Registra cómo terminó el viaje. Es una acción propia y no un cambio de
 * estado: lo que escribe es el hecho físico de la entrega, y de él sale el
 * reingreso de lo que el cliente no se quedó.
 */
class DispatchDeliveryController extends Controller
{
    public function __construct(
        private readonly DispatchDeliveryService $deliveryService,
    ) {}

    public function __invoke(
        RegisterDispatchDeliveryRequest $request,
        string $company,
        string $id,
    ): RedirectResponse {
        $this->deliveryService->execute(
            $id,
            RegisterDispatchDeliveryCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('dispatches.show', ['company' => $company, 'id' => $id]);
    }
}
