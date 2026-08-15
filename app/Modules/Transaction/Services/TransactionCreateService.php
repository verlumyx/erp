<?php

declare(strict_types=1);

namespace App\Modules\Transaction\Services;

use App\Modules\Account\Models\Account;
use App\Modules\Transaction\Commands\CreateTransactionCommand;
use App\Modules\Transaction\Exceptions\InvalidTransactionException;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Repositories\Contracts\TransactionRepositoryInterface;

class TransactionCreateService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $repository,
    ) {}

    public function execute(CreateTransactionCommand $command): Transaction
    {
        $this->guard($command);

        return $this->repository->create($command);
    }

    /**
     * Valida las reglas de negocio contables antes de persistir.
     */
    private function guard(CreateTransactionCommand $command): void
    {
        $this->assertTypeCategoryCoherence($command);
        $this->assertRelatedPairing($command);
        $this->assertStreamingAccountRelation($command);
        $this->assertPeriodOrdering($command);

        if ($command->amount < 0) {
            throw new InvalidTransactionException('El monto (amount) debe ser positivo.');
        }
    }

    private function assertTypeCategoryCoherence(CreateTransactionCommand $command): void
    {
        if (! in_array($command->type, Transaction::TYPES, true)) {
            throw new InvalidTransactionException("El tipo '{$command->type}' no es válido.");
        }

        $allowed = $command->type === Transaction::TYPE_EXPENSE
            ? Transaction::EXPENSE_CATEGORIES
            : Transaction::INCOME_CATEGORIES;

        if (! in_array($command->category, $allowed, true)) {
            throw new InvalidTransactionException(
                "La categoría '{$command->category}' no corresponde a un movimiento de tipo '{$command->type}'."
            );
        }
    }

    private function assertRelatedPairing(CreateTransactionCommand $command): void
    {
        $hasType = $command->relatedType !== null;
        $hasId = $command->relatedId !== null;

        if ($hasType !== $hasId) {
            throw new InvalidTransactionException('related_type y related_id deben informarse juntos.');
        }
    }

    private function assertStreamingAccountRelation(CreateTransactionCommand $command): void
    {
        if (! in_array($command->category, ['streaming_account', 'streaming_account_renewal'], true)) {
            return;
        }

        if ($command->relatedType !== 'Account') {
            throw new InvalidTransactionException("La categoría '{$command->category}' requiere related_type = 'Account'.");
        }

        $exists = Account::query()
            ->where('id', $command->relatedId)
            ->where('company_id', $command->companyId)
            ->exists();

        if (! $exists) {
            throw new InvalidTransactionException('La cuenta (Account) relacionada no existe.');
        }

        // Regla documentada: cuando exista el módulo de Ventas, las categorías
        // 'sale' y 'renewal' deberán exigir related_type = 'Sale' y validar su
        // existencia. Por ahora no se valida porque el módulo no existe.
    }

    private function assertPeriodOrdering(CreateTransactionCommand $command): void
    {
        $hasFrom = $command->periodFrom !== null;
        $hasTo = $command->periodTo !== null;

        if ($hasFrom !== $hasTo) {
            throw new InvalidTransactionException('period_from y period_to deben informarse juntos.');
        }

        if ($hasFrom && $hasTo && $command->periodTo < $command->periodFrom) {
            throw new InvalidTransactionException('period_to debe ser mayor o igual que period_from.');
        }
    }
}
