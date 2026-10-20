<?php

declare(strict_types=1);

namespace App\Modules\Pos\Services;

use App\Modules\Pos\Commands\OpenPosSessionCommand;
use App\Modules\Pos\Exceptions\PosRegisterBusyException;
use App\Modules\Pos\Exceptions\PosSessionAlreadyOpenException;
use App\Modules\Pos\Models\PosSession;
use App\Modules\Pos\Repositories\Contracts\PosRegisterRepositoryInterface;
use App\Modules\Pos\Repositories\Contracts\PosSessionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PosSessionOpenService
{
    public function __construct(
        private readonly PosSessionRepositoryInterface $sessionRepository,
        private readonly PosRegisterRepositoryInterface $registerRepository,
    ) {}

    public function execute(OpenPosSessionCommand $command): PosSession
    {
        return DB::transaction(function () use ($command): PosSession {
            // 1. Ensure user does not already have an active session in this company
            $userSession = $this->sessionRepository->findActiveForUser($command->userId, $command->companyId);
            if ($userSession !== null) {
                throw new PosSessionAlreadyOpenException;
            }

            // 2. Ensure register is active and not currently occupied by another user
            $register = $this->registerRepository->findOrFail($command->posRegisterId, $command->companyId);
            $registerSession = $this->sessionRepository->findActiveForRegister($register->id, $command->companyId);
            if ($registerSession !== null) {
                throw new PosRegisterBusyException;
            }

            // 3. Open new session
            return $this->sessionRepository->open($command);
        });
    }
}
