<?php

declare(strict_types=1);

namespace App\Modules\Item\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Item\Commands\UpdateStatusItemCommand;
use App\Modules\Item\Requests\UpdateStatusItemRequest;
use App\Modules\Item\Services\ItemUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class ItemUpdateStatusController extends Controller
{
    public function __construct(
        private readonly ItemUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusItemRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusItemCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('items.index', ['company' => $company])
            ->with('success', 'Estado del artículo actualizado correctamente.');
    }
}
