<?php

declare(strict_types=1);

namespace App\Modules\Pos\Repositories;

use App\Modules\Pos\Commands\CreatePosRegisterCommand;
use App\Modules\Pos\Commands\SearchPosRegisterCommand;
use App\Modules\Pos\Commands\UpdatePosRegisterCommand;
use App\Modules\Pos\Exceptions\PosRegisterNotFoundException;
use App\Modules\Pos\Models\PosRegister;
use App\Modules\Pos\Repositories\Contracts\PosRegisterRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PosRegisterRepository implements PosRegisterRepositoryInterface
{
    private const RELATIONS = [
        'warehouse',
        'priceList',
        'defaultClient',
        'creator',
        'activeSession.user',
    ];

    public function create(CreatePosRegisterCommand $command): PosRegister
    {
        return DB::transaction(function () use ($command): PosRegister {
            $code = $this->generateNextCode($command->companyId);

            return PosRegister::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $code,
                'name' => $command->name,
                'warehouse_id' => $command->warehouseId,
                'price_list_id' => $command->priceListId,
                'default_client_id' => $command->defaultClientId,
                'receipt_printer_name' => $command->receiptPrinterName,
                'invoice_series' => $command->invoiceSeries,
                'allow_price_override' => $command->allowPriceOverride,
                'allow_custom_discounts' => $command->allowCustomDiscounts,
                'max_discount_percent' => $command->maxDiscountPercent,
                'notes' => $command->notes,
                'status' => $command->status,
                'created_by' => $command->createdBy,
            ]);
        });
    }

    public function update(PosRegister $register, UpdatePosRegisterCommand $command): PosRegister
    {
        $register->update([
            'name' => $command->name,
            'warehouse_id' => $command->warehouseId,
            'price_list_id' => $command->priceListId,
            'default_client_id' => $command->defaultClientId,
            'receipt_printer_name' => $command->receiptPrinterName,
            'invoice_series' => $command->invoiceSeries,
            'allow_price_override' => $command->allowPriceOverride,
            'allow_custom_discounts' => $command->allowCustomDiscounts,
            'max_discount_percent' => $command->maxDiscountPercent,
            'notes' => $command->notes,
            'status' => $command->status,
        ]);

        return $register->refresh();
    }

    public function updateStatus(PosRegister $register, string $status): PosRegister
    {
        $register->update(['status' => $status]);

        return $register->refresh();
    }

    public function findById(string $id, ?string $companyId = null): ?PosRegister
    {
        return PosRegister::query()
            ->with(self::RELATIONS)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): PosRegister
    {
        $model = $this->findById($id, $companyId);

        if (! $model) {
            throw new PosRegisterNotFoundException;
        }

        return $model;
    }

    /**
     * @return LengthAwarePaginator<int, PosRegister>
     */
    public function paginate(SearchPosRegisterCommand $command): LengthAwarePaginator
    {
        $query = PosRegister::query()
            ->with(self::RELATIONS)
            ->where('company_id', $command->companyId);

        if ($command->search) {
            $term = '%'.trim($command->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ilike', $term)
                    ->orWhere('code', 'ilike', $term)
                    ->orWhere('invoice_series', 'ilike', $term);
            });
        }

        if (! empty($command->filters['status'])) {
            $query->where('status', $command->filters['status']);
        }

        if (! empty($command->filters['warehouse_id'])) {
            $query->where('warehouse_id', $command->filters['warehouse_id']);
        }

        $sortField = in_array($command->sortBy, ['name', 'code', 'created_at', 'status'], true)
            ? $command->sortBy
            : 'name';

        $direction = strtolower($command->sortDirection) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sortField, $direction)
            ->paginate($command->perPage, ['*'], 'page', $command->page);
    }

    /**
     * @return array<int, PosRegister>
     */
    public function listAvailable(string $companyId): array
    {
        return PosRegister::query()
            ->with(self::RELATIONS)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereDoesntHave('sessions', fn ($q) => $q->where('status', 'confirmed'))
            ->orderBy('name')
            ->get()
            ->all();
    }

    private function generateNextCode(string $companyId): string
    {
        $last = PosRegister::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', PosRegister::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(PosRegister::CODE_PREFIX))) + 1
            : 1;

        return PosRegister::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
