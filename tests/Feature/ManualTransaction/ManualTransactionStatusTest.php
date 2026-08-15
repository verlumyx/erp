<?php

declare(strict_types=1);

use App\Modules\ManualTransaction\Models\ManualTransaction;
use App\Modules\ManualTransaction\Models\ManualTransactionLine;
use App\Modules\Transaction\Models\Transaction;

use function Pest\Laravel\actingAs;

function pendingManualTransaction(string $companyId): ManualTransaction
{
    $manual = ManualTransaction::factory()->create([
        'company_id' => $companyId,
        'date' => now()->toDateString(),
        'payment_method' => 'cash',
        'currency' => 'USD',
        'total' => 140,
    ]);

    ManualTransactionLine::factory()->create([
        'manual_transaction_id' => $manual->id,
        'type' => 'income',
        'category' => 'partner_contribution',
        'amount' => 100,
    ]);
    ManualTransactionLine::factory()->create([
        'manual_transaction_id' => $manual->id,
        'type' => 'expense',
        'category' => 'salary',
        'amount' => 40,
    ]);

    return $manual;
}

test('approving a manual transaction writes one ledger transaction per line', function () {
    [$user, $company] = createUserWithCompany();
    $manual = pendingManualTransaction($company->id);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('manual-transactions.approve', ['company' => $company->id, 'id' => $manual->id]))
        ->assertRedirect(route('manual-transactions.show', ['company' => $company->id, 'id' => $manual->id]));

    expect($manual->fresh()->status)->toBe('approved');
    expect($manual->fresh()->approved_at)->not->toBeNull();

    $ledger = Transaction::where('related_type', 'ManualTransaction')
        ->where('related_id', $manual->id)
        ->orderBy('amount')
        ->get();

    expect($ledger)->toHaveCount(2);
    expect($ledger[0]->type)->toBe('expense');      // salary 40
    expect($ledger[0]->category)->toBe('salary');
    expect((float) $ledger[0]->amount)->toBe(40.00);
    expect($ledger[1]->type)->toBe('income');       // partner_contribution 100
    expect((float) $ledger[1]->amount)->toBe(100.00);
});

test('cancelling a manual transaction does not touch the ledger', function () {
    [$user, $company] = createUserWithCompany();
    $manual = pendingManualTransaction($company->id);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('manual-transactions.cancel', ['company' => $company->id, 'id' => $manual->id]))
        ->assertRedirect(route('manual-transactions.show', ['company' => $company->id, 'id' => $manual->id]));

    expect($manual->fresh()->status)->toBe('cancelled');
    expect($manual->fresh()->cancelled_at)->not->toBeNull();
    expect(Transaction::count())->toBe(0);
});

test('an approved manual transaction cannot be approved or cancelled again', function () {
    [$user, $company] = createUserWithCompany();
    $manual = pendingManualTransaction($company->id);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('manual-transactions.approve', ['company' => $company->id, 'id' => $manual->id]));

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('manual-transactions.cancel', ['company' => $company->id, 'id' => $manual->id]))
        ->assertStatus(409);

    // No se duplican movimientos contables.
    expect(Transaction::where('related_id', $manual->id)->count())->toBe(2);
});

test('a cancelled manual transaction cannot be approved', function () {
    [$user, $company] = createUserWithCompany();
    $manual = pendingManualTransaction($company->id);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('manual-transactions.cancel', ['company' => $company->id, 'id' => $manual->id]));

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('manual-transactions.approve', ['company' => $company->id, 'id' => $manual->id]))
        ->assertStatus(409);

    expect(Transaction::count())->toBe(0);
});

test('approving and cancelling require their permissions', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['manual-transactions.show']);
    $manual = pendingManualTransaction($company->id);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('manual-transactions.approve', ['company' => $company->id, 'id' => $manual->id]))
        ->assertForbidden();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('manual-transactions.cancel', ['company' => $company->id, 'id' => $manual->id]))
        ->assertForbidden();
});
