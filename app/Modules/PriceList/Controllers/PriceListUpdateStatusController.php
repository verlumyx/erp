<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PriceList\Commands\UpdateStatusPriceListCommand;
use App\Modules\PriceList\Requests\UpdateStatusPriceListRequest;
use App\Modules\PriceList\Services\PriceListUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class PriceListUpdateStatusController extends Controller
{
    public function __construct(
        private readonly PriceListUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusPriceListRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusPriceListCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('price-lists.index', ['company' => $company])
            ->with('success', 'Estado de la lista de precio actualizado correctamente.');
    }
}
