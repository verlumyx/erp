<?php

declare(strict_types=1);

namespace App\Modules\Pos\Repositories;

use App\Modules\Pos\Commands\ClosePosSessionCommand;
use App\Modules\Pos\Commands\CreatePosCashMovementCommand;
use App\Modules\Pos\Commands\OpenPosSessionCommand;
use App\Modules\Pos\Commands\PosDenominationData;
use App\Modules\Pos\Commands\PosPaymentData;
use App\Modules\Pos\Commands\SearchPosSessionCommand;
use App\Modules\Pos\Exceptions\PosSessionNotFoundException;
use App\Modules\Pos\Models\PosCashMovement;
use App\Modules\Pos\Models\PosSession;
use App\Modules\Pos\Models\PosSessionDenomination;
use App\Modules\Pos\Repositories\Contracts\PosSessionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosSessionRepository implements PosSessionRepositoryInterface
{
    private const RELATIONS = [
        'register.warehouse',
        'register.priceList',
        'register.defaultClient',
        'user',
        'supervisor',
        'creator',
        'cashMovements.authorizer',
        'denominations',
    ];

    public function open(OpenPosSessionCommand $command): PosSession
    {
        return DB::transaction(function () use ($command): PosSession {
            $code = $this->generateNextCode($command->companyId);

            return PosSession::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $code,
                'pos_register_id' => $command->posRegisterId,
                'user_id' => $command->userId,
                'opened_at' => now(),
                'opening_cash_usd' => $command->openingCashUsd,
                'opening_cash_ves' => $command->openingCashVes,
                'expected_cash_usd' => $command->openingCashUsd,
                'expected_cash_ves' => $command->openingCashVes,
                'expected_card_ves' => 0,
                'expected_mobile_ves' => 0,
                'expected_other' => 0,
                'sales_count' => 0,
                'cancelled_sales_count' => 0,
                'total_sales_amount' => 0,
                'total_discounts_amount' => 0,
                'status' => PosSession::STATUS_OPEN,
                'created_by' => $command->userId,
            ]);
        });
    }

    /**
     * @param  array{cashUsd: float, cashVes: float, cardVes: float, mobileVes: float, other: float}  $expected
     */
    public function close(PosSession $session, ClosePosSessionCommand $command, array $expected): PosSession
    {
        return DB::transaction(function () use ($session, $command, $expected): PosSession {
            // Save denominations
            if (! empty($command->denominations)) {
                $this->recordDenominations($session, $command->denominations);
            }

            $declaredUsd = $command->declaredCashUsd;
            $declaredVes = $command->declaredCashVes;

            // Calculate differences
            $diffUsd = $declaredUsd !== null ? round($declaredUsd - $expected['cashUsd'], 2) : null;
            $diffVes = $declaredVes !== null ? round($declaredVes - $expected['cashVes'], 2) : null;

            $session->update([
                'closed_at' => now(),
                'status' => PosSession::STATUS_CLOSED,
                'expected_cash_usd' => $expected['cashUsd'],
                'expected_cash_ves' => $expected['cashVes'],
                'expected_card_ves' => $expected['cardVes'],
                'expected_mobile_ves' => $expected['mobileVes'],
                'expected_other' => $expected['other'],
                'declared_cash_usd' => $declaredUsd,
                'declared_cash_ves' => $declaredVes,
                'declared_card_ves' => $command->declaredCardVes,
                'declared_mobile_ves' => $command->declaredMobileVes,
                'declared_other' => $command->declaredOther,
                'difference_cash_usd' => $diffUsd,
                'difference_cash_ves' => $diffVes,
                'supervisor_id' => $command->supervisorId,
                'closing_notes' => $command->notes,
            ]);

            return $session->refresh();
        });
    }

    public function findActiveForUser(string $userId, string $companyId): ?PosSession
    {
        return PosSession::query()
            ->with(self::RELATIONS)
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('status', PosSession::STATUS_OPEN)
            ->first();
    }

    public function findActiveForRegister(string $registerId, string $companyId): ?PosSession
    {
        return PosSession::query()
            ->with(self::RELATIONS)
            ->where('company_id', $companyId)
            ->where('pos_register_id', $registerId)
            ->where('status', PosSession::STATUS_OPEN)
            ->first();
    }

    public function findById(string $id, ?string $companyId = null): ?PosSession
    {
        return PosSession::query()
            ->with(self::RELATIONS)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): PosSession
    {
        $session = $this->findById($id, $companyId);

        if (! $session) {
            throw new PosSessionNotFoundException;
        }

        return $session;
    }

    /**
     * @return LengthAwarePaginator<int, PosSession>
     */
    public function paginate(SearchPosSessionCommand $command): LengthAwarePaginator
    {
        $query = PosSession::query()
            ->with(self::RELATIONS)
            ->where('company_id', $command->companyId);

        if ($command->search) {
            $term = '%'.trim($command->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('code', 'ilike', $term)
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'ilike', $term))
                    ->orWhereHas('register', fn ($rq) => $rq->where('name', 'ilike', $term));
            });
        }

        if (! empty($command->filters['status'])) {
            $query->where('status', $command->filters['status']);
        }

        if (! empty($command->filters['user_id'])) {
            $query->where('user_id', $command->filters['user_id']);
        }

        if (! empty($command->filters['pos_register_id'])) {
            $query->where('pos_register_id', $command->filters['pos_register_id']);
        }

        if (! empty($command->filters['from_date'])) {
            $query->where('opened_at', '>=', $command->filters['from_date']);
        }

        if (! empty($command->filters['to_date'])) {
            $query->where('opened_at', '<=', $command->filters['to_date']);
        }

        $sortField = in_array($command->sortBy, ['opened_at', 'closed_at', 'code', 'total_sales_amount'], true)
            ? $command->sortBy
            : 'opened_at';

        $direction = strtolower($command->sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $direction)
            ->paginate($command->perPage, ['*'], 'page', $command->page);
    }

    /**
     * @param  array<int, PosPaymentData>  $payments
     */
    public function accumulateSale(PosSession $session, float $totalAmount, float $discountAmount, array $payments): void
    {
        $session->increment('sales_count', 1);
        $session->increment('total_sales_amount', $totalAmount);
        $session->increment('total_discounts_amount', $discountAmount);
    }

    public function recordCashMovement(CreatePosCashMovementCommand $command, float $exchangeRate): PosCashMovement
    {
        return PosCashMovement::create([
            'id' => $command->id,
            'company_id' => $command->companyId,
            'pos_session_id' => $command->posSessionId,
            'movement_type' => $command->movementType,
            'concept' => $command->concept,
            'currency' => $command->currency,
            'exchange_rate' => $exchangeRate,
            'amount' => $command->amount,
            'reason' => $command->reason,
            'authorized_by' => $command->authorizedBy,
            'status' => 'active',
            'created_by' => $command->createdBy,
        ]);
    }

    /**
     * @param  array<int, PosDenominationData>  $denominations
     */
    public function recordDenominations(PosSession $session, array $denominations): void
    {
        // Remove existing denominations if re-counting
        PosSessionDenomination::where('pos_session_id', $session->id)->delete();

        foreach ($denominations as $denom) {
            PosSessionDenomination::create([
                'id' => (string) Str::uuid(),
                'company_id' => $session->company_id,
                'pos_session_id' => $session->id,
                'currency' => $denom->currency,
                'denomination' => $denom->denomination,
                'count' => $denom->count,
                'total_amount' => $denom->totalAmount,
                'status' => 'active',
            ]);
        }
    }

    private function generateNextCode(string $companyId): string
    {
        $last = PosSession::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', PosSession::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(PosSession::CODE_PREFIX))) + 1
            : 1;

        return PosSession::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
