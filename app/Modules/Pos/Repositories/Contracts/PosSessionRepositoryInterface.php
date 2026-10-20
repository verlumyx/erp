<?php

declare(strict_types=1);

namespace App\Modules\Pos\Repositories\Contracts;

use App\Modules\Pos\Commands\ClosePosSessionCommand;
use App\Modules\Pos\Commands\CreatePosCashMovementCommand;
use App\Modules\Pos\Commands\OpenPosSessionCommand;
use App\Modules\Pos\Commands\PosDenominationData;
use App\Modules\Pos\Commands\PosPaymentData;
use App\Modules\Pos\Commands\SearchPosSessionCommand;
use App\Modules\Pos\Models\PosCashMovement;
use App\Modules\Pos\Models\PosSession;
use Illuminate\Pagination\LengthAwarePaginator;

interface PosSessionRepositoryInterface
{
    public function open(OpenPosSessionCommand $command): PosSession;

    /**
     * @param  array{cashUsd: float, cashVes: float, cardVes: float, mobileVes: float, other: float}  $expected
     */
    public function close(PosSession $session, ClosePosSessionCommand $command, array $expected): PosSession;

    public function findActiveForUser(string $userId, string $companyId): ?PosSession;

    public function findActiveForRegister(string $registerId, string $companyId): ?PosSession;

    public function findById(string $id, ?string $companyId = null): ?PosSession;

    public function findOrFail(string $id, ?string $companyId = null): PosSession;

    /**
     * @return LengthAwarePaginator<int, PosSession>
     */
    public function paginate(SearchPosSessionCommand $command): LengthAwarePaginator;

    /**
     * @param  array<int, PosPaymentData>  $payments
     */
    public function accumulateSale(PosSession $session, float $totalAmount, float $discountAmount, array $payments): void;

    public function recordCashMovement(CreatePosCashMovementCommand $command, float $exchangeRate): PosCashMovement;

    /**
     * @param  array<int, PosDenominationData>  $denominations
     */
    public function recordDenominations(PosSession $session, array $denominations): void;
}
