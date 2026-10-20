<?php

declare(strict_types=1);

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Commands\SearchPosSessionCommand;
use App\Modules\Pos\Repositories\Contracts\PosRegisterRepositoryInterface;
use App\Modules\Pos\Repositories\Contracts\PosSessionRepositoryInterface;
use App\Modules\Pos\Resources\PosRegisterResource;
use App\Modules\Pos\Resources\PosSessionResource;
use App\Modules\Pos\Resources\PosSessionZReportResource;
use App\Modules\Pos\Services\PosSessionCalculationService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosSessionGetController extends Controller
{
    public function __construct(
        private readonly PosSessionRepositoryInterface $sessionRepository,
        private readonly PosRegisterRepositoryInterface $registerRepository,
        private readonly PosSessionCalculationService $calculationService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('pos-sessions.list') ?? false, 403);

        $companyId = session('current_company_id');
        $command = new SearchPosSessionCommand(
            companyId: $companyId,
            filters: $request->only(['status', 'user_id', 'pos_register_id', 'from_date', 'to_date']),
            search: $request->string('search')->toString() ?: null,
            sortBy: $request->string('sort_by', 'opened_at')->toString(),
            sortDirection: $request->string('sort_direction', 'desc')->toString(),
            perPage: $request->integer('per_page', 15),
            page: $request->integer('page', 1),
        );

        $paginator = $this->sessionRepository->paginate($command);

        return Inertia::render('pos/sessions/index', [
            'sessions' => PosSessionResource::collection($paginator),
            'filters' => [
                'search' => $command->search,
                'status' => $command->filters['status'] ?? null,
                'user_id' => $command->filters['user_id'] ?? null,
                'pos_register_id' => $command->filters['pos_register_id'] ?? null,
                'from_date' => $command->filters['from_date'] ?? null,
                'to_date' => $command->filters['to_date'] ?? null,
            ],
            'registers' => $this->registerRepository->listAvailable($companyId),
            'users' => User::whereHas('companies', fn ($q) => $q->where('app_companies.id', $companyId))->get(['id', 'name']),
        ]);
    }

    public function openForm(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('pos-sessions.open') ?? false, 403);

        $companyId = session('current_company_id');
        $user = $request->user();

        // Check if user already has an active session -> redirect to POS terminal directly
        $existing = $this->sessionRepository->findActiveForUser($user->id, $companyId);
        if ($existing) {
            return Inertia::render('pos/sessions/open', [
                'alreadyOpen' => true,
                'currentSession' => (new PosSessionResource($existing))->resolve(),
                'availableRegisters' => [],
            ]);
        }

        $availableRegisters = $this->registerRepository->listAvailable($companyId);

        return Inertia::render('pos/sessions/open', [
            'alreadyOpen' => false,
            'currentSession' => null,
            'availableRegisters' => PosRegisterResource::collection($availableRegisters),
        ]);
    }

    public function show(Request $request, string $company, string $id): Response
    {
        abort_unless($request->user()?->hasPermission('pos-sessions.read') ?? false, 403);

        $companyId = session('current_company_id');
        $session = $this->sessionRepository->findOrFail($id, $companyId);

        return Inertia::render('pos/sessions/show', [
            'report' => (new PosSessionZReportResource($session))->resolve(),
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        $companyId = session('current_company_id');
        $user = $request->user();

        $session = $this->sessionRepository->findActiveForUser($user->id, $companyId);

        return response()->json([
            'session' => $session ? (new PosSessionResource($session))->resolve() : null,
        ]);
    }

    public function readingX(Request $request, string $company, string $id): JsonResponse
    {
        $companyId = session('current_company_id');
        $session = $this->sessionRepository->findOrFail($id, $companyId);

        return response()->json([
            'reading' => (new PosSessionZReportResource($session))->resolve(),
        ]);
    }
}
