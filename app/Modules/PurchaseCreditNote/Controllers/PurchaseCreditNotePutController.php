<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseCreditNote\Commands\UpdatePurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Requests\UpdatePurchaseCreditNoteRequest;
use App\Modules\PurchaseCreditNote\Services\PurchaseCreditNoteUpdateService;
use Illuminate\Http\RedirectResponse;

class PurchaseCreditNotePutController extends Controller
{
    public function __construct(
        private readonly PurchaseCreditNoteUpdateService $updateService,
    ) {}

    public function __invoke(UpdatePurchaseCreditNoteRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdatePurchaseCreditNoteCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('purchase-credit-notes.show', ['company' => $company, 'id' => $id]);
    }
}
