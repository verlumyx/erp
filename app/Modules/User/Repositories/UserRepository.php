<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Modules\User\Commands\CreateUserCommand;
use App\Modules\User\Commands\SearchUserCommand;
use App\Modules\User\Commands\UpdateStatusUserCommand;
use App\Modules\User\Commands\UpdateUserCommand;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;

class UserRepository extends UserFilters implements UserRepositoryInterface
{
    public function create(CreateUserCommand $command): void
    {
        User::create([
            'id' => $command->id,
            'name' => $command->name,
            'email' => $command->email,
            'password' => $command->password,
        ]);
    }

    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    public function findOrFail(string $id): User
    {
        return User::findOrFail($id);
    }

    public function update(User $model, UpdateUserCommand $command): void
    {
        $data = [
            'name' => $command->name,
            'email' => $command->email,
        ];

        if ($command->password !== null) {
            $data['password'] = $command->password;
        }

        $model->update($data);
    }

    public function updateStatus(User $model, UpdateStatusUserCommand $command): void
    {
        $model->update([
            'is_active' => $command->isActive,
        ]);
    }

    /**
     * @return array{ data: User[], total: int }
     */
    public function search(SearchUserCommand $command): array
    {
        $query = User::query()
            ->when($command->companyId, fn ($q) => $q->whereHas('companies', fn ($c) => $c->where('company_id', $command->companyId)));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->limit($command->limit)->offset($command->offset)->get();

        return ['data' => $data->all(), 'total' => $total];
    }
}
