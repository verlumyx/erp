<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Store\Commands\CreateStoreItemCommand;
use App\Modules\Store\Exceptions\ItemAlreadyPublishedException;
use App\Modules\Store\Requests\CreateStoreItemRequest;
use App\Modules\Store\Services\StoreItemCreateService;
use Illuminate\Http\RedirectResponse;

class StoreItemPostController extends Controller
{
    public function __construct(
        private readonly StoreItemCreateService $createService,
    ) {}

    /**
     * Tras crear se abre la edición: la galería se carga ahí, después de
     * que la publicación existe.
     */
    public function __invoke(CreateStoreItemRequest $request, string $company): RedirectResponse
    {
        try {
            $storeItem = $this->createService->execute(
                CreateStoreItemCommand::fromRequest($request, $company),
            );
        } catch (ItemAlreadyPublishedException $exception) {
            return back()->withInput()->withErrors(['item_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('store-items.edit', ['company' => $company, 'id' => $storeItem->id])
            ->with('success', 'Publicación creada. Ahora puedes cargar sus fotos.');
    }
}
