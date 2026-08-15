<?php

declare(strict_types=1);

namespace App\Modules\Account\Repositories\Contracts;

use App\Modules\Account\Commands\CreateAccountCommand;
use App\Modules\Account\Commands\RenewAccountCommand;
use App\Modules\Account\Commands\SearchAccountCommand;
use App\Modules\Account\Commands\UpdateAccountCommand;
use App\Modules\Account\Models\Account;

interface AccountRepositoryInterface
{
    public function create(CreateAccountCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?Account;

    public function findOrFail(string $id, ?string $companyId = null): Account;

    public function update(Account $model, UpdateAccountCommand $command): void;

    public function renew(Account $model, RenewAccountCommand $command): void;

    /** @return array{ data: Account[], total: int } */
    public function search(SearchAccountCommand $command): array;
}
