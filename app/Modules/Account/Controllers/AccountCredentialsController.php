<?php

declare(strict_types=1);

namespace App\Modules\Account\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Models\Account;
use App\Modules\Account\Services\AccountCredentialsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AccountCredentialsController extends Controller
{
    public function __construct(
        private readonly AccountCredentialsService $credentialsService,
    ) {}

    public function __invoke(Request $request, string $company, string $id): JsonResponse
    {
        // Solo admin/supervisor (permiso accounts.credentials) pueden ver credentials.
        Gate::authorize('viewCredentials', Account::class);

        $credentials = $this->credentialsService->execute(
            $id,
            (string) $request->user()->id,
            $company,
        );

        return response()->json($credentials);
    }
}
