<?php

declare(strict_types=1);

namespace App\Modules\Permission\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Permission\Commands\UpdatePermissionCommand;
use App\Modules\Permission\Requests\UpdatePermissionRequest;
use App\Modules\Permission\Services\PermissionUpdateService;
use Illuminate\Http\RedirectResponse;

class PermissionPutController extends Controller
{
    public function __construct(
        private readonly PermissionUpdateService $updateService,
    ) {}

    public function __invoke(UpdatePermissionRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdatePermissionCommand::fromRequest($request)
        );

        return redirect()->route('permissions.show', ['company' => $company, 'id' => $id]);
    }
}
