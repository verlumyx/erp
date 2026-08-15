<?php

declare(strict_types=1);

namespace App\Modules\Role\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Role\Commands\UpdateStatusRoleCommand;
use App\Modules\Role\Requests\UpdateStatusRoleRequest;
use App\Modules\Role\Services\RoleUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class RoleUpdateStatusController extends Controller
{
    public function __construct(
        private readonly RoleUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusRoleRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusRoleCommand::fromRequest($request)
        );

        return redirect()->route('roles.index', ['company' => $company])
            ->with('success', 'Estado del rol actualizado correctamente.');
    }
}
