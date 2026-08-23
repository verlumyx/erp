<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ItemLot\Commands\UpdateStatusItemLotCommand;
use App\Modules\ItemLot\Requests\UpdateStatusItemLotRequest;
use App\Modules\ItemLot\Services\ItemLotUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class ItemLotUpdateStatusController extends Controller
{
    public function __construct(
        private readonly ItemLotUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusItemLotRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusItemLotCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('item-lots.index', ['company' => $company])
            ->with('success', 'Estado del lote actualizado correctamente.');
    }
}
