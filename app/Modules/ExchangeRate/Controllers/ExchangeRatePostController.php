<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ExchangeRate\Commands\CreateExchangeRateCommand;
use App\Modules\ExchangeRate\Requests\CreateExchangeRateRequest;
use App\Modules\ExchangeRate\Services\ExchangeRateCreateService;
use Illuminate\Http\RedirectResponse;

class ExchangeRatePostController extends Controller
{
    public function __construct(
        private readonly ExchangeRateCreateService $createService,
    ) {}

    public function __invoke(CreateExchangeRateRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateExchangeRateCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('exchange-rates.index', ['company' => $request->route('company')]);
    }
}
