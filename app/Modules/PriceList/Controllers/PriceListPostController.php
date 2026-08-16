<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PriceList\Commands\CreatePriceListCommand;
use App\Modules\PriceList\Requests\CreatePriceListRequest;
use App\Modules\PriceList\Services\PriceListCreateService;
use Illuminate\Http\RedirectResponse;

class PriceListPostController extends Controller
{
    public function __construct(
        private readonly PriceListCreateService $createService,
    ) {}

    public function __invoke(CreatePriceListRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreatePriceListCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('price-lists.index', ['company' => $request->route('company')]);
    }
}
