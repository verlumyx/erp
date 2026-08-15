<?php

declare(strict_types=1);

namespace App\Modules\Account\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Commands\SearchAccountCommand;
use App\Modules\Account\Resources\AccountResource;
use App\Modules\Account\Services\AccountFindService;
use App\Modules\Account\Services\AccountSearchService;
use App\Modules\Service\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountGetController extends Controller
{
    public function __construct(
        private readonly AccountSearchService $searchService,
        private readonly AccountFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('accounts.list') ?? false, 403);

        $command = new SearchAccountCommand(
            filters: $request->only(['code', 'email', 'status', 'service_id']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('accounts/index', [
            'accounts' => AccountResource::collection($result['data'])->resolve(),
            'services' => $this->activeServices(session('current_company_id')),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['code', 'email', 'status', 'service_id', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('accounts.create') ?? false, 403);

        return Inertia::render('accounts/create', [
            'services' => $this->activeServices(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('accounts.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('accounts/show', [
            'account' => (new AccountResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('accounts.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('accounts/edit', [
            'account' => (new AccountResource($model))->resolve(),
            'services' => $this->activeServices($company),
        ]);
    }

    /**
     * Services activos de la compañía, para poblar el select del formulario de account.
     *
     * @return array<int, array{id: string, name: string, code: string, max_profiles: int}>
     */
    private function activeServices(?string $companyId): array
    {
        if ($companyId === null) {
            return [];
        }

        return Service::query()
            ->where('company_id', $companyId)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'max_profiles'])
            ->map(fn (Service $service): array => [
                'id' => $service->id,
                'name' => $service->name,
                'code' => $service->code,
                'max_profiles' => $service->max_profiles,
            ])
            ->all();
    }
}
