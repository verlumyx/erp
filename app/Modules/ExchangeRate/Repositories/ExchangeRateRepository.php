<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Repositories;

use App\Modules\ExchangeRate\Commands\CreateExchangeRateCommand;
use App\Modules\ExchangeRate\Commands\SearchExchangeRateCommand;
use App\Modules\ExchangeRate\Commands\UpdateExchangeRateCommand;
use App\Modules\ExchangeRate\Commands\UpdateStatusExchangeRateCommand;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\ExchangeRate\Repositories\Contracts\ExchangeRateRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ExchangeRateRepository extends ExchangeRateFilters implements ExchangeRateRepositoryInterface
{
    /**
     * Only one rate may exist per company, currency, date and type. Loading the same
     * combination again updates the existing record — and reactivates it — instead of
     * creating a second one.
     */
    public function create(CreateExchangeRateCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            $existing = $this->findByRateKey(
                $command->companyId,
                $command->currency,
                $command->rateDate,
                $command->type,
            );

            if ($existing !== null) {
                $existing->update([
                    'rate' => $command->rate,
                    'source' => $command->source,
                    'description' => $command->description,
                    'status' => 'active',
                ]);

                return;
            }

            ExchangeRate::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'currency' => $command->currency,
                'rate_date' => $command->rateDate,
                'rate' => $command->rate,
                'type' => $command->type,
                'source' => $command->source,
                'description' => $command->description,
                'status' => 'active',
                'created_by' => $command->createdBy,
            ]);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?ExchangeRate
    {
        return ExchangeRate::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): ExchangeRate
    {
        return ExchangeRate::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function findByRateKey(string $companyId, string $currency, string $rateDate, string $type): ?ExchangeRate
    {
        return ExchangeRate::query()
            ->where('company_id', $companyId)
            ->where('currency', $currency)
            ->whereDate('rate_date', $rateDate)
            ->where('type', $type)
            ->first();
    }

    public function update(ExchangeRate $model, UpdateExchangeRateCommand $command): void
    {
        $model->update([
            'currency' => $command->currency,
            'rate_date' => $command->rateDate,
            'rate' => $command->rate,
            'type' => $command->type,
            'source' => $command->source,
            'description' => $command->description,
        ]);
    }

    public function updateStatus(ExchangeRate $model, UpdateStatusExchangeRateCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /**
     * @return array{ data: ExchangeRate[], total: int }
     */
    public function search(SearchExchangeRateCommand $command): array
    {
        $query = ExchangeRate::query()
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('rate_date')
            ->orderBy('currency')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Generate the next sequential per-company code (TAS000001, TAS000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = ExchangeRate::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', ExchangeRate::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(ExchangeRate::CODE_PREFIX))) + 1
            : 1;

        return ExchangeRate::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
