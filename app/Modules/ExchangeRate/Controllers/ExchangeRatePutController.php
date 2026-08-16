<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ExchangeRate\Commands\UpdateExchangeRateCommand;
use App\Modules\ExchangeRate\Requests\UpdateExchangeRateRequest;
use App\Modules\ExchangeRate\Services\ExchangeRateUpdateService;
use Illuminate\Http\RedirectResponse;

class ExchangeRatePutController extends Controller
{
    public function __construct(
        private readonly ExchangeRateUpdateService $updateService,
    ) {}

    public function __invoke(UpdateExchangeRateRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateExchangeRateCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('exchange-rates.show', ['company' => $company, 'id' => $id]);
    }
}
