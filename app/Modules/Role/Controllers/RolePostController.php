<?php

declare(strict_types=1);

namespace App\Modules\Role\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Role\Commands\CreateRoleCommand;
use App\Modules\Role\Requests\CreateRoleRequest;
use App\Modules\Role\Services\RoleCreateService;
use Illuminate\Http\RedirectResponse;

class RolePostController extends Controller
{
    public function __construct(
        private readonly RoleCreateService $createService,
    ) {}

    public function __invoke(CreateRoleRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateRoleCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('roles.index', ['company' => $request->route('company')]);
    }
}
