<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Warehouse\Commands\CreateWarehouseCommand;
use App\Modules\Warehouse\Requests\CreateWarehouseRequest;
use App\Modules\Warehouse\Services\WarehouseCreateService;
use Illuminate\Http\RedirectResponse;

class WarehousePostController extends Controller
{
    public function __construct(
        private readonly WarehouseCreateService $createService,
    ) {}

    public function __invoke(CreateWarehouseRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateWarehouseCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('warehouses.index', ['company' => $request->route('company')]);
    }
}
