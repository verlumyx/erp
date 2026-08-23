<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ItemStock\Commands\UpdateStatusItemStockCommand;
use App\Modules\ItemStock\Requests\UpdateStatusItemStockRequest;
use App\Modules\ItemStock\Services\ItemStockUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class ItemStockUpdateStatusController extends Controller
{
    public function __construct(
        private readonly ItemStockUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusItemStockRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusItemStockCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('item-stocks.index', ['company' => $company])
            ->with('success', 'Estado del saldo actualizado correctamente.');
    }
}
