<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories\Contracts;

use App\Modules\User\Commands\CreateUserCommand;
use App\Modules\User\Commands\SearchUserCommand;
use App\Modules\User\Commands\UpdateStatusUserCommand;
use App\Modules\User\Commands\UpdateUserCommand;
use App\Modules\User\Models\User;

interface UserRepositoryInterface
{
    public function create(CreateUserCommand $command): void;

    public function findById(string $id): ?User;

    public function findOrFail(string $id): User;

    public function update(User $model, UpdateUserCommand $command): void;

    public function updateStatus(User $model, UpdateStatusUserCommand $command): void;

    /** @return array{ data: User[], total: int } */
    public function search(SearchUserCommand $command): array;
}
