<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ItemSerial\Commands\UpdateStatusItemSerialCommand;
use App\Modules\ItemSerial\Requests\UpdateStatusItemSerialRequest;
use App\Modules\ItemSerial\Services\ItemSerialUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class ItemSerialUpdateStatusController extends Controller
{
    public function __construct(
        private readonly ItemSerialUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusItemSerialRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusItemSerialCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('item-serials.index', ['company' => $company])
            ->with('success', 'Estado de la serie actualizado correctamente.');
    }
}
