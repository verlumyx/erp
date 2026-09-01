<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesCreditNote\Commands\UpdateSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Requests\UpdateSalesCreditNoteRequest;
use App\Modules\SalesCreditNote\Services\SalesCreditNoteUpdateService;
use Illuminate\Http\RedirectResponse;

class SalesCreditNotePutController extends Controller
{
    public function __construct(
        private readonly SalesCreditNoteUpdateService $updateService,
    ) {}

    public function __invoke(UpdateSalesCreditNoteRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateSalesCreditNoteCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('sales-credit-notes.show', ['company' => $company, 'id' => $id]);
    }
}
