<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Role\Repositories\Contracts\RoleRepositoryInterface;
use App\Modules\Shared\Models\UserCompany;
use App\Modules\User\Commands\SearchUserCommand;
use App\Modules\User\Models\User;
use App\Modules\User\Services\UserFindService;
use App\Modules\User\Services\UserSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserGetController extends Controller
{
    public function __construct(
        private readonly UserSearchService $searchService,
        private readonly UserFindService $findService,
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    public function index(Request $request, string $company): Response
    {
        abort_unless($request->user()?->hasPermission('users.list') ?? false, 403);

        $command = new SearchUserCommand(
            filters: $request->only(['name', 'email', 'email_verified']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: $company,
        );

        $result = $this->searchService->execute($command);

        $users = $result['data'];
        $userIds = array_map(fn (User $u) => $u->id, $users);

        $userCompanies = UserCompany::whereIn('user_id', $userIds)
            ->where('company_id', $company)
            ->with('role')
            ->get()
            ->keyBy('user_id');

        $formattedUsers = array_map(function (User $user) use ($userCompanies): array {
            $uc = $userCompanies->get($user->id);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'status' => $uc?->status ?? 'inactive',
                'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
                'role' => $uc?->role ? ['id' => $uc->role->id, 'name' => $uc->role->name] : null,
            ];
        }, $users);

        return Inertia::render('users/index', [
            'users' => $formattedUsers,
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['name', 'email', 'email_verified', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request, string $company): Response
    {
        abort_unless($request->user()?->hasPermission('users.create') ?? false, 403);

        return Inertia::render('users/create', [
            'roles' => $this->getActiveRoles($company),
        ]);
    }

    public function checkEmail(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('users.create') ?? false, 403);

        $email = $request->string('email')->toString();
        $user = User::where('email', $email)->first();

        $alreadyInCompany = $user && UserCompany::where('user_id', $user->id)
            ->where('company_id', $company)
            ->exists();

        return response()->json([
            'exists' => $user !== null,
            'already_in_company' => $alreadyInCompany,
            'user' => $user ? ['id' => $user->id, 'name' => $user->name] : null,
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('users.show') ?? false, 403);

        $model = $this->findService->execute($id);

        return Inertia::render('users/show', [
            'user' => $this->formatUser($model),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('users.update') ?? false, 403);

        $model = $this->findService->execute($id);

        return Inertia::render('users/edit', [
            'user' => $this->formatUser($model),
            'roles' => $this->getActiveRoles($company),
        ]);
    }

    /** @return array<string, mixed> */
    private function formatUser(User $user): array
    {
        $companyId = session('current_company_id');
        $userCompany = $companyId
            ? UserCompany::where('user_id', $user->id)->where('company_id', $companyId)->with('role')->first()
            : null;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $user->updated_at?->format('Y-m-d H:i:s'),
            'role' => $userCompany?->role ? ['id' => $userCompany->role->id, 'name' => $userCompany->role->name] : null,
            'role_id' => $userCompany?->role_id,
        ];
    }

    /** @return array<array{id: string, name: string, description: string}> */
    private function getActiveRoles(string $companyId): array
    {
        return $this->roleRepository->getActive($companyId);
    }
}
