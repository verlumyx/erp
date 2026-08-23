<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SupplierAdvance\Commands\CreateSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Requests\CreateSupplierAdvanceRequest;
use App\Modules\SupplierAdvance\Services\SupplierAdvanceCreateService;
use Illuminate\Http\RedirectResponse;

class SupplierAdvancePostController extends Controller
{
    public function __construct(
        private readonly SupplierAdvanceCreateService $createService,
    ) {}

    public function __invoke(CreateSupplierAdvanceRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateSupplierAdvanceCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('supplier-advances.index', ['company' => $request->route('company')]);
    }
}
