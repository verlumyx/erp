<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ItemLot\Commands\UpdateItemLotCommand;
use App\Modules\ItemLot\Requests\UpdateItemLotRequest;
use App\Modules\ItemLot\Services\ItemLotUpdateService;
use Illuminate\Http\RedirectResponse;

class ItemLotPutController extends Controller
{
    public function __construct(
        private readonly ItemLotUpdateService $updateService,
    ) {}

    public function __invoke(UpdateItemLotRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateItemLotCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('item-lots.show', ['company' => $company, 'id' => $id]);
    }
}
