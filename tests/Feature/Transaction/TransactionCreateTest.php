<?php

declare(strict_types=1);

use App\Modules\Account\Models\Account;
use App\Modules\Service\Models\Service;
use App\Modules\Transaction\Commands\CreateTransactionCommand;
use App\Modules\Transaction\Exceptions\InvalidTransactionException;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Services\TransactionCreateService;
use Illuminate\Support\Str;

/**
 * Construye un comando de creación válido (gasto general) con overrides.
 *
 * @param  array<string, mixed>  $overrides
 */
function makeCreateCommand(string $companyId, string $userId, array $overrides = []): CreateTransactionCommand
{
    $defaults = [
        'id' => (string) Str::uuid7(),
        'companyId' => $companyId,
        'type' => 'expense',
        'category' => 'utilities',
        'amount' => 49.90,
        'date' => now()->toDateString(),
        'paymentMethod' => 'cash',
        'description' => 'Pago de servicios',
        'recordedBy' => $userId,
    ];

    $args = array_merge($defaults, $overrides);

    return new CreateTransactionCommand(...$args);
}

function createTransaction(string $companyId, string $userId, array $overrides = []): Transaction
{
    return app(TransactionCreateService::class)->execute(
        makeCreateCommand($companyId, $userId, $overrides)
    );
}

test('an expense transaction can be created', function () {
    [$user, $company] = createUserWithCompany();

    $transaction = createTransaction($company->id, $user->id, [
        'category' => 'salary',
        'amount' => 1200.00,
    ]);

    expect($transaction)->not->toBeNull();
    expect($transaction->type)->toBe('expense');
    expect($transaction->category)->toBe('salary');
    expect($transaction->company_id)->toBe($company->id);
    expect($transaction->recorded_by)->toBe($user->id);
    expect((float) $transaction->amount)->toBe(1200.00);
    expect($transaction->currency)->toBe('USD');

    $this->assertDatabaseHas('app_transactions', [
        'id' => $transaction->id,
        'company_id' => $company->id,
        'type' => 'expense',
        'category' => 'salary',
    ]);
});

test('an income transaction can be created', function () {
    [$user, $company] = createUserWithCompany();

    $transaction = createTransaction($company->id, $user->id, [
        'type' => 'income',
        'category' => 'sale',
        'amount' => 25.00,
        'description' => 'Venta de cuenta',
    ]);

    expect($transaction->type)->toBe('income');
    expect($transaction->category)->toBe('sale');
});

test('a transaction supports soft delete', function () {
    [$user, $company] = createUserWithCompany();

    $transaction = createTransaction($company->id, $user->id);
    $transaction->delete();

    expect($transaction->trashed())->toBeTrue();
    $this->assertSoftDeleted('app_transactions', ['id' => $transaction->id]);
});

test('an expense category cannot be used with type income', function () {
    [$user, $company] = createUserWithCompany();

    expect(fn () => createTransaction($company->id, $user->id, [
        'type' => 'income',
        'category' => 'salary',
    ]))->toThrow(InvalidTransactionException::class);
});

test('an income category cannot be used with type expense', function () {
    [$user, $company] = createUserWithCompany();

    expect(fn () => createTransaction($company->id, $user->id, [
        'type' => 'expense',
        'category' => 'sale',
    ]))->toThrow(InvalidTransactionException::class);
});

test('related_type without related_id is rejected', function () {
    [$user, $company] = createUserWithCompany();

    expect(fn () => createTransaction($company->id, $user->id, [
        'relatedType' => 'Account',
    ]))->toThrow(InvalidTransactionException::class);
});

test('streaming_account requires an existing related Account', function () {
    [$user, $company] = createUserWithCompany();

    expect(fn () => createTransaction($company->id, $user->id, [
        'category' => 'streaming_account',
        'relatedType' => 'Account',
        'relatedId' => (string) Str::uuid7(),
    ]))->toThrow(InvalidTransactionException::class);
});

test('streaming_account transaction is created when the Account exists', function () {
    [$user, $company] = createUserWithCompany();

    $service = Service::factory()->create(['company_id' => $company->id]);
    $account = Account::factory()->create([
        'company_id' => $company->id,
        'service_id' => $service->id,
    ]);

    $transaction = createTransaction($company->id, $user->id, [
        'category' => 'streaming_account',
        'relatedType' => 'Account',
        'relatedId' => $account->id,
        'amount' => 12.50,
    ]);

    expect($transaction->related_type)->toBe('Account');
    expect($transaction->related_id)->toBe($account->id);
    expect($transaction->related)->not->toBeNull();
    expect($transaction->related->is($account))->toBeTrue();
});

test('an inverted period is rejected', function () {
    [$user, $company] = createUserWithCompany();

    expect(fn () => createTransaction($company->id, $user->id, [
        'periodFrom' => '2026-02-01',
        'periodTo' => '2026-01-01',
    ]))->toThrow(InvalidTransactionException::class);
});
