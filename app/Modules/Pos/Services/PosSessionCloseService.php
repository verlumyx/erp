<?php

declare(strict_types=1);

namespace App\Modules\Pos\Services;

use App\Modules\Pos\Commands\ClosePosSessionCommand;
use App\Modules\Pos\Exceptions\PosSessionClosedException;
use App\Modules\Pos\Exceptions\PosUnauthorizedSessionException;
use App\Modules\Pos\Models\PosSession;
use App\Modules\Pos\Repositories\Contracts\PosSessionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PosSessionCloseService
{
    public function __construct(
        private readonly PosSessionRepositoryInterface $sessionRepository,
        private readonly PosSessionCalculationService $calculationService,
    ) {}

    public function execute(ClosePosSessionCommand $command): PosSession
    {
        return DB::transaction(function () use ($command): PosSession {
            $session = $this->sessionRepository->findOrFail($command->sessionId, $command->companyId);

            if (! $session->isOpen()) {
                throw new PosSessionClosedException;
            }

            // Only session owner or supervisor can close
            if ($session->user_id !== $command->userId && $command->supervisorId === null) {
                // If user is not session owner and did not provide supervisor authorization
                throw new PosUnauthorizedSessionException;
            }

            // Calculate expected figures
            $expected = $this->calculationService->calculate($session);

            // Execute close
            return $this->sessionRepository->close($session, $command, $expected);
        });
    }
}
