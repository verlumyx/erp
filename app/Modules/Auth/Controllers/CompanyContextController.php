<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Resources\AuthCompanyResource;
use App\Modules\Menu\Services\GetActiveMenusService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyContextController extends Controller
{
    public function __construct(
        private readonly GetActiveMenusService $getActiveMenusService,
    ) {}

    /**
     * Returns everything the mobile app needs after selecting a company in a
     * single request: the company membership, the permissions of the user's
     * role in that company, and the menu tree filtered by those permissions.
     */
    public function __invoke(Request $request, string $company): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $membership = $user->companies()->where('company_id', $company)->firstOrFail();

        return response()->json([
            'company' => new AuthCompanyResource($membership),
            'permissions' => $user->getPermissions(),
            'menu' => $this->getActiveMenusService->execute($user),
        ]);
    }
}
