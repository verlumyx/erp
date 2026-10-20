<?php

declare(strict_types=1);

namespace App\Modules\Pos\Repositories\Contracts;

use App\Modules\Pos\Commands\CreatePosRegisterCommand;
use App\Modules\Pos\Commands\SearchPosRegisterCommand;
use App\Modules\Pos\Commands\UpdatePosRegisterCommand;
use App\Modules\Pos\Models\PosRegister;
use Illuminate\Pagination\LengthAwarePaginator;

interface PosRegisterRepositoryInterface
{
    public function create(CreatePosRegisterCommand $command): PosRegister;

    public function update(PosRegister $register, UpdatePosRegisterCommand $command): PosRegister;

    public function updateStatus(PosRegister $register, string $status): PosRegister;

    public function findById(string $id, ?string $companyId = null): ?PosRegister;

    public function findOrFail(string $id, ?string $companyId = null): PosRegister;

    /**
     * @return LengthAwarePaginator<int, PosRegister>
     */
    public function paginate(SearchPosRegisterCommand $command): LengthAwarePaginator;

    /**
     * @return array<int, PosRegister>
     */
    public function listAvailable(string $companyId): array;
}
