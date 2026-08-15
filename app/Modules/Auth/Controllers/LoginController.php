<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Commands\LoginCommand;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Resources\AuthUserResource;
use App\Modules\Auth\Services\LoginService;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(
        private readonly LoginService $loginService,
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $result = $this->loginService->execute(
            LoginCommand::fromRequest($request)
        );

        return response()->json([
            'token' => $result['token'],
            'user' => new AuthUserResource($result['user']->load('companies')),
        ], 201);
    }
}
