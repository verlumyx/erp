<?php

declare(strict_types=1);

namespace App\Modules\Permission\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Permission\Commands\CreatePermissionCommand;
use App\Modules\Permission\Requests\CreatePermissionRequest;
use App\Modules\Permission\Services\PermissionCreateService;
use Illuminate\Http\RedirectResponse;

class PermissionPostController extends Controller
{
    public function __construct(
        private readonly PermissionCreateService $createService,
    ) {}

    public function __invoke(CreatePermissionRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreatePermissionCommand::fromRequest($request)
        );

        return redirect()->route('permissions.index', ['company' => $request->route('company')]);
    }
}
