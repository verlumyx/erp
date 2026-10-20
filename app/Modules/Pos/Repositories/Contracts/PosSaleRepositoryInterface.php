<?php

declare(strict_types=1);

namespace App\Modules\Pos\Repositories\Contracts;

use App\Modules\Pos\Commands\HoldPosSaleCommand;
use App\Modules\Pos\Commands\PosCheckoutCommand;
use App\Modules\Pos\Commands\SearchPosSaleCommand;
use App\Modules\Pos\Models\PosSale;
use Illuminate\Pagination\LengthAwarePaginator;

interface PosSaleRepositoryInterface
{
    public function create(
        PosCheckoutCommand $command,
        ?string $invoiceId,
        ?string $dispatchId,
        float $exchangeRate
    ): PosSale;

    public function createDraft(HoldPosSaleCommand $command, float $exchangeRate): PosSale;

    public function cancel(PosSale $sale, string $cancelledBy, ?string $reason = null): PosSale;

    public function deleteDraft(PosSale $sale): void;

    /**
     * @return array<int, PosSale>
     */
    public function getHeldSales(string $sessionId): array;

    public function findById(string $id, ?string $companyId = null): ?PosSale;

    public function findOrFail(string $id, ?string $companyId = null): PosSale;

    /**
     * @return LengthAwarePaginator<int, PosSale>
     */
    public function paginate(SearchPosSaleCommand $command): LengthAwarePaginator;
}
