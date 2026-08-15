<?php

declare(strict_types=1);

use App\Modules\Transaction\Commands\CreateTransactionCommand;
use App\Modules\Transaction\Exceptions\InvalidTransactionException;
use App\Modules\Transaction\Services\TransactionCreateService;
use Illuminate\Support\Str;

/**
 * Construye un comando de transacción para la categoría refund con overrides.
 *
 * @param  array<string, mixed>  $overrides
 */
function makeRefundTransactionCommand(string $companyId, string $userId, array $overrides = []): CreateTransactionCommand
{
    return new CreateTransactionCommand(...array_merge([
        'id' => (string) Str::uuid7(),
        'companyId' => $companyId,
        'type' => 'expense',
        'category' => 'refund',
        'amount' => 25.0,
        'date' => now()->toDateString(),
        'paymentMethod' => 'cash',
        'description' => 'Reembolso',
        'recordedBy' => $userId,
    ], $overrides));
}

test('the refund category is accounted as an expense', function () {
    [$user, $company] = createUserWithCompany();

    $transaction = app(TransactionCreateService::class)->execute(
        makeRefundTransactionCommand($company->id, $user->id, [
            'relatedType' => 'Refund',
            'relatedId' => (string) Str::uuid7(),
        ])
    );

    expect($transaction->type)->toBe('expense');
    expect($transaction->category)->toBe('refund');
});

test('a refund cannot be recorded as income', function () {
    [$user, $company] = createUserWithCompany();

    expect(fn () => app(TransactionCreateService::class)->execute(
        makeRefundTransactionCommand($company->id, $user->id, ['type' => 'income'])
    ))->toThrow(InvalidTransactionException::class);
});
