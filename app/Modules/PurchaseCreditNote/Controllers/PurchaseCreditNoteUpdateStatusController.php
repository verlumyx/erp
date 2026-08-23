<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseCreditNote\Commands\UpdateStatusPurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Requests\UpdateStatusPurchaseCreditNoteRequest;
use App\Modules\PurchaseCreditNote\Services\PurchaseCreditNoteUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class PurchaseCreditNoteUpdateStatusController extends Controller
{
    public function __construct(
        private readonly PurchaseCreditNoteUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusPurchaseCreditNoteRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusPurchaseCreditNoteCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('purchase-credit-notes.show', ['company' => $company, 'id' => $id]);
    }
}
