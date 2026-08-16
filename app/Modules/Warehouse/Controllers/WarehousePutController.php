<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Warehouse\Commands\UpdateWarehouseCommand;
use App\Modules\Warehouse\Requests\UpdateWarehouseRequest;
use App\Modules\Warehouse\Services\WarehouseUpdateService;
use Illuminate\Http\RedirectResponse;

class WarehousePutController extends Controller
{
    public function __construct(
        private readonly WarehouseUpdateService $updateService,
    ) {}

    public function __invoke(UpdateWarehouseRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateWarehouseCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('warehouses.show', ['company' => $company, 'id' => $id]);
    }
}
