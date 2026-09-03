<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Store\Commands\UpdateStoreItemCommand;
use App\Modules\Store\Requests\UpdateStoreItemRequest;
use App\Modules\Store\Services\StoreItemUpdateService;
use Illuminate\Http\RedirectResponse;

class StoreItemPutController extends Controller
{
    public function __construct(
        private readonly StoreItemUpdateService $updateService,
    ) {}

    public function __invoke(UpdateStoreItemRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateStoreItemCommand::fromRequest($request),
            $company,
        );

        return redirect()
            ->route('store-items.edit', ['company' => $company, 'id' => $id])
            ->with('success', 'Publicación actualizada.');
    }
}
