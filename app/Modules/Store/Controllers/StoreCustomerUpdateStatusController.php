<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Store\Commands\UpdateStatusStoreCustomerCommand;
use App\Modules\Store\Requests\UpdateStatusStoreCustomerRequest;
use App\Modules\Store\Services\StoreCustomerUpdateStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreCustomerUpdateStatusController extends Controller
{
    public function __construct(
        private readonly StoreCustomerUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusStoreCustomerRequest $request, string $company, string $id): RedirectResponse
    {
        try {
            $customer = DB::transaction(fn () => $this->updateStatusService->execute(
                $id,
                $company,
                UpdateStatusStoreCustomerCommand::fromRequest($request),
            ));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return back()->with('error', 'No se pudo cambiar el estado del comprador: '.$exception->getMessage());
        }

        $message = $customer->status === 'active'
            ? "Comprador {$customer->code} activado."
            : "Comprador {$customer->code} bloqueado: ya no puede iniciar sesión.";

        return back()->with('success', $message);
    }
}
