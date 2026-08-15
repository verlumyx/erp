<?php

declare(strict_types=1);

namespace App\Modules\Refund\Repositories\Contracts;

use App\Modules\Refund\Commands\CreateRefundCommand;
use App\Modules\Refund\Commands\ResolveRefundCommand;
use App\Modules\Refund\Commands\SearchRefundCommand;
use App\Modules\Refund\Commands\UpdateRefundCommand;
use App\Modules\Refund\Models\Refund;

interface RefundRepositoryInterface
{
    public function create(CreateRefundCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?Refund;

    public function findOrFail(string $id, ?string $companyId = null): Refund;

    public function update(Refund $model, UpdateRefundCommand $command): void;

    public function approve(Refund $model, ResolveRefundCommand $command): void;

    public function reject(Refund $model, ResolveRefundCommand $command): void;

    /** @return array{ data: Refund[], total: int } */
    public function search(SearchRefundCommand $command): array;
}
