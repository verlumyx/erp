<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesCreditNote\Commands\UpdateStatusSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Requests\UpdateStatusSalesCreditNoteRequest;
use App\Modules\SalesCreditNote\Services\SalesCreditNoteUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class SalesCreditNoteUpdateStatusController extends Controller
{
    public function __construct(
        private readonly SalesCreditNoteUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusSalesCreditNoteRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusSalesCreditNoteCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('sales-credit-notes.show', ['company' => $company, 'id' => $id]);
    }
}
