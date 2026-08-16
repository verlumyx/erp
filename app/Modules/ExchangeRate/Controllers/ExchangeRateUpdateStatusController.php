<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ExchangeRate\Commands\UpdateStatusExchangeRateCommand;
use App\Modules\ExchangeRate\Requests\UpdateStatusExchangeRateRequest;
use App\Modules\ExchangeRate\Services\ExchangeRateUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class ExchangeRateUpdateStatusController extends Controller
{
    public function __construct(
        private readonly ExchangeRateUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusExchangeRateRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusExchangeRateCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('exchange-rates.index', ['company' => $company])
            ->with('success', 'Estado de la tasa actualizado correctamente.');
    }
}
