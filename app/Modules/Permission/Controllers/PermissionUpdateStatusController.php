<?php

declare(strict_types=1);

namespace App\Modules\Permission\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Permission\Commands\UpdateStatusPermissionCommand;
use App\Modules\Permission\Requests\UpdateStatusPermissionRequest;
use App\Modules\Permission\Services\PermissionUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class PermissionUpdateStatusController extends Controller
{
    public function __construct(
        private readonly PermissionUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusPermissionRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusPermissionCommand::fromRequest($request)
        );

        return redirect()->route('permissions.show', ['company' => $company, 'id' => $id]);
    }
}
