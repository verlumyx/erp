<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SupplierType\Commands\CreateSupplierTypeCommand;
use App\Modules\SupplierType\Requests\CreateSupplierTypeRequest;
use App\Modules\SupplierType\Services\SupplierTypeCreateService;
use Illuminate\Http\RedirectResponse;

class SupplierTypePostController extends Controller
{
    public function __construct(
        private readonly SupplierTypeCreateService $createService,
    ) {}

    public function __invoke(CreateSupplierTypeRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateSupplierTypeCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('supplier-types.index', ['company' => $request->route('company')]);
    }
}
