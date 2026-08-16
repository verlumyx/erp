<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Supplier\Commands\CreateSupplierCommand;
use App\Modules\Supplier\Requests\CreateSupplierRequest;
use App\Modules\Supplier\Services\SupplierCreateService;
use Illuminate\Http\RedirectResponse;

class SupplierPostController extends Controller
{
    public function __construct(
        private readonly SupplierCreateService $createService,
    ) {}

    public function __invoke(CreateSupplierRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateSupplierCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('suppliers.index', ['company' => $request->route('company')]);
    }
}
