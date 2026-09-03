<?php

declare(strict_types=1);

namespace App\Modules\Menu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Menu\Services\GetActiveMenusService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuApiController extends Controller
{
    public function __construct(
        private readonly GetActiveMenusService $getActiveMenusService,
    ) {}

    /**
     * Returns the active menu tree for the authenticated user, filtered by the
     * permissions of their role in the given company. Items keep their raw urls
     * (e.g. `/services`); the mobile client maps them to its own navigation.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** La empresa la deja en sesión el middleware `company.access.api`. */
        $companyId = session('current_company_id');

        return response()->json(
            $this->getActiveMenusService->execute($user, is_string($companyId) ? $companyId : null)
        );
    }
}
