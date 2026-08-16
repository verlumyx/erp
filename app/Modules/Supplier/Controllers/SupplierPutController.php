<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Supplier\Commands\UpdateSupplierCommand;
use App\Modules\Supplier\Requests\UpdateSupplierRequest;
use App\Modules\Supplier\Services\SupplierUpdateService;
use Illuminate\Http\RedirectResponse;

class SupplierPutController extends Controller
{
    public function __construct(
        private readonly SupplierUpdateService $updateService,
    ) {}

    public function __invoke(UpdateSupplierRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateSupplierCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('suppliers.show', ['company' => $company, 'id' => $id]);
    }
}
