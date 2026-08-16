<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WarehouseLocation\Commands\UpdateWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Requests\UpdateWarehouseLocationRequest;
use App\Modules\WarehouseLocation\Services\WarehouseLocationUpdateService;
use Illuminate\Http\RedirectResponse;

class WarehouseLocationPutController extends Controller
{
    public function __construct(
        private readonly WarehouseLocationUpdateService $updateService,
    ) {}

    public function __invoke(UpdateWarehouseLocationRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateWarehouseLocationCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('warehouse-locations.show', ['company' => $company, 'id' => $id]);
    }
}
