<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Store\Commands\ConvertStoreOrderCommand;
use App\Modules\Store\Requests\ConvertStoreOrderRequest;
use App\Modules\Store\Services\StoreOrderConvertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * La transacción la abre el servicio: incluye cliente, vínculo, dirección,
 * orden y cierre del pedido web.
 */
class StoreOrderConvertController extends Controller
{
    public function __construct(
        private readonly StoreOrderConvertService $convertService,
    ) {}

    public function __invoke(ConvertStoreOrderRequest $request, string $company, string $id): RedirectResponse
    {
        try {
            $result = $this->convertService->execute(
                ConvertStoreOrderCommand::fromRequest($request, $company, $id),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return back()->with('error', 'No se pudo convertir el pedido web: '.$exception->getMessage());
        }

        $message = "Pedido web {$result['order']->code} convertido en la orden {$result['sales_order']->code}.";

        if ($result['notice'] !== null) {
            $message .= ' '.$result['notice'];
        }

        return redirect()
            ->route('sales-orders.show', ['company' => $company, 'id' => $result['sales_order']->id])
            ->with('success', $message);
    }
}
