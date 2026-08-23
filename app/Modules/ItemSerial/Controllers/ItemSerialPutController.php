<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ItemSerial\Commands\UpdateItemSerialCommand;
use App\Modules\ItemSerial\Requests\UpdateItemSerialRequest;
use App\Modules\ItemSerial\Services\ItemSerialUpdateService;
use Illuminate\Http\RedirectResponse;

class ItemSerialPutController extends Controller
{
    public function __construct(
        private readonly ItemSerialUpdateService $updateService,
    ) {}

    public function __invoke(UpdateItemSerialRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateItemSerialCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('item-serials.show', ['company' => $company, 'id' => $id]);
    }
}
