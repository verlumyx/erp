<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Store\Commands\RejectStoreOrderCommand;
use App\Modules\Store\Requests\RejectStoreOrderRequest;
use App\Modules\Store\Services\StoreOrderRejectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreOrderRejectController extends Controller
{
    public function __construct(
        private readonly StoreOrderRejectService $rejectService,
    ) {}

    public function __invoke(RejectStoreOrderRequest $request, string $company, string $id): RedirectResponse
    {
        try {
            $order = DB::transaction(fn () => $this->rejectService->execute(
                RejectStoreOrderCommand::fromRequest($request, $company, $id),
            ));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return back()->with('error', 'No se pudo rechazar el pedido web: '.$exception->getMessage());
        }

        return back()->with('success', "Pedido web {$order->code} rechazado.");
    }
}
