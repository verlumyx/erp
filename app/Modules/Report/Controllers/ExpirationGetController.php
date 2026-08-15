<?php

declare(strict_types=1);

namespace App\Modules\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Services\ExpirationSummaryService;
use App\Modules\Sale\Commands\SearchSaleCommand;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Resources\SaleResource;
use App\Modules\Sale\Services\SaleSearchService;
use App\Modules\Service\Models\Service;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpirationGetController extends Controller
{
    private const ALLOWED_DAYS = [7, 15, 30];

    private const ALLOWED_STATUSES = ['expiring', 'expired', 'all'];

    public function __construct(
        private readonly SaleSearchService $searchService,
        private readonly ExpirationSummaryService $summaryService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('reports.expirations') ?? false, 403);

        $companyId = (string) session('current_company_id');

        $days = $request->integer('days', 7);
        if (! in_array($days, self::ALLOWED_DAYS, true)) {
            $days = 7;
        }

        $status = $request->string('status')->toString();
        if (! in_array($status, self::ALLOWED_STATUSES, true)) {
            $status = 'expiring';
        }

        $serviceId = $request->string('service_id')->toString() ?: null;
        $agentId = $request->string('agent_id')->toString() ?: null;

        $explicitFrom = $request->date('date_from')?->toDateString();
        $explicitTo = $request->date('date_to')?->toDateString();

        $expiringFrom = $explicitFrom ?? now()->toDateString();
        $expiringTo = $explicitTo ?? now()->addDays($days)->toDateString();

        $listFilters = array_filter([
            'service_id' => $serviceId,
            'agent_id' => $agentId,
        ], fn ($value) => $value !== null);

        if ($status === 'expiring') {
            $listFilters['status'] = Sale::STATUS_ACTIVE;
            $listFilters['end_date_from'] = $expiringFrom;
            $listFilters['end_date_to'] = $expiringTo;
        } elseif ($status === 'expired') {
            $listFilters['status'] = Sale::STATUS_EXPIRED;
            $listFilters['end_date_from'] = $explicitFrom;
            $listFilters['end_date_to'] = $explicitTo;
        } else {
            $listFilters['status_in'] = [Sale::STATUS_ACTIVE, Sale::STATUS_EXPIRED];
            $listFilters['end_date_from'] = $explicitFrom;
            $listFilters['end_date_to'] = $explicitTo;
        }

        $command = new SearchSaleCommand(
            filters: $listFilters,
            limit: $request->integer('limit', 50),
            offset: $request->integer('offset', 0),
            companyId: $companyId,
        );

        $summaryCommand = new SearchSaleCommand(
            filters: array_filter([
                'expiring_from' => $expiringFrom,
                'expiring_to' => $expiringTo,
                'date_from' => $explicitFrom,
                'date_to' => $explicitTo,
                'service_id' => $serviceId,
                'agent_id' => $agentId,
            ], fn ($value) => $value !== null),
            companyId: $companyId,
        );

        $searched = $request->boolean('searched');

        $result = $searched
            ? $this->searchService->searchExpirations($command)
            : ['data' => [], 'total' => 0];

        $summary = $searched
            ? $this->summaryService->execute($summaryCommand)
            : ['expiring_count' => 0, 'expiring_amount' => 0.0, 'expired_count' => 0, 'expired_amount' => 0.0, 'renewal_rate' => 0.0];

        return Inertia::render('reports/expirations/index', [
            'searched' => $searched,
            'sales' => SaleResource::collection($result['data'])->resolve(),
            'summary' => $summary,
            'services' => $this->companyServices($companyId),
            'agents' => $this->companyAgents($companyId),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => [
                'days' => $days,
                'status' => $status,
                'date_from' => $explicitFrom,
                'date_to' => $explicitTo,
                'service_id' => $serviceId,
                'agent_id' => $agentId,
                'limit' => $command->limit,
                'offset' => $command->offset,
            ],
        ]);
    }

    /**
     * @return array<int, array{id: string, name: string, code: ?string}>
     */
    private function companyServices(string $companyId): array
    {
        return Service::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Service $service): array => [
                'id' => $service->id,
                'name' => $service->name,
                'code' => $service->code,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    private function companyAgents(string $companyId): array
    {
        return User::query()
            ->whereHas('companies', fn ($q) => $q->where('app_companies.id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->all();
    }
}
