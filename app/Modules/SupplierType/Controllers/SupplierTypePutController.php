<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SupplierType\Commands\UpdateSupplierTypeCommand;
use App\Modules\SupplierType\Requests\UpdateSupplierTypeRequest;
use App\Modules\SupplierType\Services\SupplierTypeUpdateService;
use Illuminate\Http\RedirectResponse;

class SupplierTypePutController extends Controller
{
    public function __construct(
        private readonly SupplierTypeUpdateService $updateService,
    ) {}

    public function __invoke(UpdateSupplierTypeRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateSupplierTypeCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('supplier-types.show', ['company' => $company, 'id' => $id]);
    }
}
