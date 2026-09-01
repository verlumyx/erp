<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesCreditNote\Commands\CreateSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Requests\CreateSalesCreditNoteRequest;
use App\Modules\SalesCreditNote\Services\SalesCreditNoteCreateService;
use Illuminate\Http\RedirectResponse;

class SalesCreditNotePostController extends Controller
{
    public function __construct(
        private readonly SalesCreditNoteCreateService $createService,
    ) {}

    public function __invoke(CreateSalesCreditNoteRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateSalesCreditNoteCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('sales-credit-notes.index', ['company' => $request->route('company')]);
    }
}
