<?php

declare(strict_types=1);

namespace App\Modules\Item\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Item\Commands\UpdateItemCommand;
use App\Modules\Item\Requests\UpdateItemRequest;
use App\Modules\Item\Services\ItemUpdateService;
use Illuminate\Http\RedirectResponse;

class ItemPutController extends Controller
{
    public function __construct(
        private readonly ItemUpdateService $updateService,
    ) {}

    public function __invoke(UpdateItemRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateItemCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('items.show', ['company' => $company, 'id' => $id]);
    }
}
