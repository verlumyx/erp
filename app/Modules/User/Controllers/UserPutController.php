<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Commands\UpdateUserCommand;
use App\Modules\User\Requests\UpdateUserRequest;
use App\Modules\User\Services\UserUpdateService;
use Illuminate\Http\RedirectResponse;

class UserPutController extends Controller
{
    public function __construct(
        private readonly UserUpdateService $updateService,
    ) {}

    public function __invoke(UpdateUserRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateUserCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('users.show', ['company' => $company, 'id' => $id]);
    }
}
