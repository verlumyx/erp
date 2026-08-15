<?php

declare(strict_types=1);

use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

function manualTxValidationPayload(array $overrides = []): array
{
    return array_merge([
        'id' => (string) Str::uuid7(),
        'date' => now()->toDateString(),
        'payment_method' => 'cash',
        'currency' => 'USD',
        'lines' => [
            ['category' => 'partner_contribution', 'amount' => 10],
        ],
    ], $overrides);
}

test('it rejects a manual transaction without lines', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('manual-transactions.store', ['company' => $company->id]), manualTxValidationPayload(['lines' => []]))
        ->assertSessionHasErrors('lines');
});

test('it rejects a line with an invalid category', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('manual-transactions.store', ['company' => $company->id]), manualTxValidationPayload([
            'lines' => [['category' => 'not_a_category', 'amount' => 10]],
        ]))
        ->assertSessionHasErrors('lines.0.category');
});

test('it rejects a line with a negative amount', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('manual-transactions.store', ['company' => $company->id]), manualTxValidationPayload([
            'lines' => [['category' => 'salary', 'amount' => -5]],
        ]))
        ->assertSessionHasErrors('lines.0.amount');
});
