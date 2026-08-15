<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Commands\UpdateStatusUserCommand;
use App\Modules\User\Requests\UpdateStatusUserRequest;
use App\Modules\User\Services\UserUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class UserUpdateStatusController extends Controller
{
    public function __construct(
        private readonly UserUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusUserRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            $company,
            UpdateStatusUserCommand::fromRequest($request)
        );

        return redirect()->route('users.index', ['company' => $company])
            ->with('success', 'Estado del usuario actualizado correctamente.');
    }
}
