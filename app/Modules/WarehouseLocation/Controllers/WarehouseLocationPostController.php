<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WarehouseLocation\Commands\CreateWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Requests\CreateWarehouseLocationRequest;
use App\Modules\WarehouseLocation\Services\WarehouseLocationCreateService;
use Illuminate\Http\RedirectResponse;

class WarehouseLocationPostController extends Controller
{
    public function __construct(
        private readonly WarehouseLocationCreateService $createService,
    ) {}

    public function __invoke(CreateWarehouseLocationRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateWarehouseLocationCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('warehouse-locations.index', ['company' => $request->route('company')]);
    }
}
