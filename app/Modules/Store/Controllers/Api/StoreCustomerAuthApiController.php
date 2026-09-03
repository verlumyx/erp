<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateStoreKey;
use App\Modules\Store\Commands\RegisterStoreCustomerCommand;
use App\Modules\Store\Requests\Api\AcceptInvitationApiRequest;
use App\Modules\Store\Requests\Api\LoginStoreCustomerApiRequest;
use App\Modules\Store\Requests\Api\RegisterStoreCustomerApiRequest;
use App\Modules\Store\Resources\Api\StoreCustomerApiResource;
use App\Modules\Store\Services\StoreCustomerAcceptInvitationService;
use App\Modules\Store\Services\StoreCustomerLoginService;
use App\Modules\Store\Services\StoreCustomerRegisterService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Cuenta del comprador: registro, inicio de sesión y aceptación de la
 * invitación. Los tres devuelven el token Sanctum con la habilidad
 * `store-customer`, que la tienda guarda en una cookie `httpOnly`.
 */
class StoreCustomerAuthApiController extends Controller
{
    public function __construct(
        private readonly StoreCustomerRegisterService $registerService,
        private readonly StoreCustomerLoginService $loginService,
        private readonly StoreCustomerAcceptInvitationService $acceptInvitationService,
    ) {}

    public function register(RegisterStoreCustomerApiRequest $request): JsonResponse
    {
        $result = $this->registerService->execute(RegisterStoreCustomerCommand::fromRequest(
            $request,
            $this->companyId($request),
            Hash::make($request->string('password')->toString()),
        ));

        return $this->tokenResponse($result, 201);
    }

    public function login(LoginStoreCustomerApiRequest $request): JsonResponse
    {
        try {
            $result = $this->loginService->execute(
                $this->companyId($request),
                strtolower(trim($request->string('email')->toString())),
                $request->string('password')->toString(),
            );
        } catch (AuthorizationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        }

        return $this->tokenResponse($result, 200);
    }

    public function acceptInvitation(AcceptInvitationApiRequest $request, string $token): JsonResponse
    {
        $result = $this->acceptInvitationService->execute(
            $this->companyId($request),
            $token,
            $request->string('password')->toString(),
        );

        return $this->tokenResponse($result, 200);
    }

    private function companyId(Request $request): string
    {
        return (string) $request->attributes->get(AuthenticateStoreKey::REQUEST_ATTRIBUTE);
    }

    /**
     * @param  array{ customer: \App\Modules\Store\Models\StoreCustomer, token: string }  $result
     */
    private function tokenResponse(array $result, int $status): JsonResponse
    {
        return response()->json([
            'token' => $result['token'],
            'customer' => (new StoreCustomerApiResource($result['customer']))->resolve(),
        ], $status);
    }
}
