<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SupplierAdvance\Commands\UpdateSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Requests\UpdateSupplierAdvanceRequest;
use App\Modules\SupplierAdvance\Services\SupplierAdvanceUpdateService;
use Illuminate\Http\RedirectResponse;

class SupplierAdvancePutController extends Controller
{
    public function __construct(
        private readonly SupplierAdvanceUpdateService $updateService,
    ) {}

    public function __invoke(UpdateSupplierAdvanceRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateSupplierAdvanceCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('supplier-advances.show', ['company' => $company, 'id' => $id]);
    }
}
