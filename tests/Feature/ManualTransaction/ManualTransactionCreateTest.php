<?php

declare(strict_types=1);

use App\Modules\ManualTransaction\Models\ManualTransaction;
use App\Modules\ManualTransaction\Models\ManualTransactionLine;
use App\Modules\Transaction\Models\Transaction;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

function manualTxPayload(array $overrides = []): array
{
    return array_merge([
        'id' => (string) Str::uuid7(),
        'date' => now()->toDateString(),
        'payment_method' => 'cash',
        'currency' => 'USD',
        'reference' => 'REF-001',
        'description' => 'Cierre de caja',
        'notes' => null,
        'lines' => [
            ['category' => 'partner_contribution', 'amount' => 100, 'description' => 'Aporte'],
            ['category' => 'salary', 'amount' => 40, 'description' => 'Pago'],
        ],
    ], $overrides);
}

test('a manual transaction is created with header and lines, deriving the type', function () {
    [$user, $company] = createUserWithCompany();
    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('manual-transactions.store', ['company' => $company->id]), manualTxPayload(['id' => $id]));

    $response->assertRedirect(route('manual-transactions.show', ['company' => $company->id, 'id' => $id]));
    $response->assertSessionHasNoErrors();

    $manual = ManualTransaction::find($id);
    expect($manual)->not->toBeNull();
    expect($manual->code)->toBe('MTX000001');
    expect((float) $manual->total)->toBe(140.00);
    expect($manual->status)->toBe('pending');

    $lines = ManualTransactionLine::where('manual_transaction_id', $id)->orderBy('created_at')->get();
    expect($lines)->toHaveCount(2);
    expect($lines[0]->type)->toBe('income');   // partner_contribution
    expect($lines[1]->type)->toBe('expense');  // salary
});

test('manual transactions never write to the app_transactions ledger', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('manual-transactions.store', ['company' => $company->id]), manualTxPayload());

    expect(Transaction::count())->toBe(0);
});

test('manual transaction codes are sequential per company', function () {
    [$user, $company] = createUserWithCompany();

    foreach ([1, 2] as $_) {
        actingAs($user)
            ->withSession(['current_company_id' => $company->id])
            ->post(route('manual-transactions.store', ['company' => $company->id]), manualTxPayload());
    }

    $codes = ManualTransaction::where('company_id', $company->id)->orderBy('code')->pluck('code')->all();
    expect($codes)->toBe(['MTX000001', 'MTX000002']);
});
