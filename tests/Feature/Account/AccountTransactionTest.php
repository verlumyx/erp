<?php

declare(strict_types=1);

use App\Modules\Account\Models\Account;
use App\Modules\Service\Models\Service;
use App\Modules\Transaction\Models\Transaction;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: \App\Modules\User\Models\User, 1: \App\Modules\Company\Models\Company, 2: \App\Modules\Service\Models\Service}
 */
function makeAccountTransactionContext(): array
{
    [$user, $company] = createUserWithCompany();

    $service = Service::factory()->create([
        'company_id' => $company->id,
        'max_profiles' => 3,
    ]);

    return [$user, $company, $service];
}

test('creating an account records a streaming_account expense transaction', function () {
    [$user, $company, $service] = makeAccountTransactionContext();

    $id = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.store', ['company' => $company->id]), [
            'id' => $id,
            'service_id' => $service->id,
            'email' => 'netflix@test.com',
            'password' => 'secret',
            'cost' => 15.00,
            'purchase_date' => '2026-06-01',
            'next_renewal' => '2026-07-01',
        ])
        ->assertSessionHasNoErrors();

    $transactions = Transaction::where('related_type', 'Account')
        ->where('related_id', $id)
        ->get();

    expect($transactions)->toHaveCount(1);

    $transaction = $transactions->first();
    expect($transaction->type)->toBe('expense');
    expect($transaction->category)->toBe('streaming_account');
    expect($transaction->company_id)->toBe($company->id);
    expect((float) $transaction->amount)->toBe(15.00);
    expect($transaction->payment_method)->toBe('cash');
    expect($transaction->date->toDateString())->toBe('2026-06-01');
    expect($transaction->period_from->toDateString())->toBe('2026-06-01');
    expect($transaction->period_to->toDateString())->toBe('2026-07-01');
    expect($transaction->recorded_by)->toBe($user->id);
    expect($transaction->related->is(Account::find($id)))->toBeTrue();
});

test('renewing an account records a renewal expense transaction', function () {
    [$user, $company, $service] = makeAccountTransactionContext();

    // La cuenta se crea vía factory (no pasa por el repositorio), así que no
    // hay transacción de compra previa: solo debe aparecer la de renovación.
    $account = Account::factory()->forService($service)->create([
        'cost' => 10.00,
        'purchase_date' => '2026-06-01',
        'next_renewal' => '2026-07-01',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.renew', ['company' => $company->id, 'id' => $account->id]), [
            'id' => (string) Str::uuid7(),
            'amount' => 12.50,
            'next_renewal' => '2026-08-01',
            'notes' => 'Pago mensual',
        ])
        ->assertSessionHasNoErrors();

    $transactions = Transaction::where('related_type', 'Account')
        ->where('related_id', $account->id)
        ->get();

    expect($transactions)->toHaveCount(1);

    $transaction = $transactions->first();
    expect($transaction->type)->toBe('expense');
    expect($transaction->category)->toBe('streaming_account_renewal');
    expect((float) $transaction->amount)->toBe(12.50);
    expect($transaction->payment_method)->toBe('cash');
    expect($transaction->period_from->toDateString())->toBe('2026-07-01');
    expect($transaction->period_to->toDateString())->toBe('2026-08-01');
    expect($transaction->recorded_by)->toBe($user->id);

    // La renovación también actualiza el costo de la cuenta al monto pagado.
    $account->refresh();
    expect($account->next_renewal->toDateString())->toBe('2026-08-01');
    expect((float) $account->cost)->toBe(12.50);
});
