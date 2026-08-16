<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ExchangeRate\Commands\SearchExchangeRateCommand;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\ExchangeRate\Resources\ExchangeRateResource;
use App\Modules\ExchangeRate\Services\ExchangeRateFindService;
use App\Modules\ExchangeRate\Services\ExchangeRateSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExchangeRateGetController extends Controller
{
    public function __construct(
        private readonly ExchangeRateSearchService $searchService,
        private readonly ExchangeRateFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('exchange-rates.list') ?? false, 403);

        $command = new SearchExchangeRateCommand(
            filters: $request->only(['code', 'currency', 'rate_date', 'type', 'status']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('exchange-rates/index', [
            'exchangeRates' => array_map(
                fn (ExchangeRate $rate): array => (new ExchangeRateResource($rate))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['code', 'currency', 'rate_date', 'type', 'status', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('exchange-rates.create') ?? false, 403);

        return Inertia::render('exchange-rates/create');
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('exchange-rates.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('exchange-rates/show', [
            'exchangeRate' => (new ExchangeRateResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('exchange-rates.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('exchange-rates/edit', [
            'exchangeRate' => (new ExchangeRateResource($model))->resolve(),
        ]);
    }
}
