<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transfer\Commands\UpdateTransferCommand;
use App\Modules\Transfer\Requests\UpdateTransferRequest;
use App\Modules\Transfer\Services\TransferUpdateService;
use Illuminate\Http\RedirectResponse;

class TransferPutController extends Controller
{
    public function __construct(
        private readonly TransferUpdateService $updateService,
    ) {}

    public function __invoke(UpdateTransferRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateTransferCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('transfers.show', ['company' => $company, 'id' => $id]);
    }
}
