<?php

declare(strict_types=1);

namespace App\Modules\Role\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Role\Commands\UpdateRoleCommand;
use App\Modules\Role\Requests\UpdateRoleRequest;
use App\Modules\Role\Services\RoleUpdateService;
use Illuminate\Http\RedirectResponse;

class RolePutController extends Controller
{
    public function __construct(
        private readonly RoleUpdateService $updateService,
    ) {}

    public function __invoke(UpdateRoleRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateRoleCommand::fromRequest($request)
        );

        return redirect()->route('roles.show', ['company' => $company, 'id' => $id]);
    }
}
