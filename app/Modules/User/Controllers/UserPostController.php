<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Commands\CreateUserCommand;
use App\Modules\User\Requests\CreateUserRequest;
use App\Modules\User\Services\UserCreateService;
use Illuminate\Http\RedirectResponse;

class UserPostController extends Controller
{
    public function __construct(
        private readonly UserCreateService $createService,
    ) {}

    public function __invoke(CreateUserRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateUserCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('users.index', ['company' => $request->route('company')]);
    }
}
