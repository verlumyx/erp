<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PriceList\Commands\UpdatePriceListCommand;
use App\Modules\PriceList\Requests\UpdatePriceListRequest;
use App\Modules\PriceList\Services\PriceListUpdateService;
use Illuminate\Http\RedirectResponse;

class PriceListPutController extends Controller
{
    public function __construct(
        private readonly PriceListUpdateService $updateService,
    ) {}

    public function __invoke(UpdatePriceListRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdatePriceListCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('price-lists.show', ['company' => $company, 'id' => $id]);
    }
}
