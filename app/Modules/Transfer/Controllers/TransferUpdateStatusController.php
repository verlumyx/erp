<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transfer\Commands\UpdateStatusTransferCommand;
use App\Modules\Transfer\Requests\UpdateStatusTransferRequest;
use App\Modules\Transfer\Services\TransferUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class TransferUpdateStatusController extends Controller
{
    public function __construct(
        private readonly TransferUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusTransferRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusTransferCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('transfers.show', ['company' => $company, 'id' => $id]);
    }
}
