<?php

declare(strict_types=1);

use App\Modules\ManualTransaction\Models\ManualTransaction;
use App\Modules\ManualTransaction\Models\ManualTransactionLine;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('listing requires the manual-transactions.list permission', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('manual-transactions.index', ['company' => $company->id]))
        ->assertForbidden();
});

test('creating requires the manual-transactions.create permission', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['manual-transactions.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('manual-transactions.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'date' => now()->toDateString(),
            'payment_method' => 'cash',
            'currency' => 'USD',
            'lines' => [['category' => 'salary', 'amount' => 10]],
        ])
        ->assertForbidden();
});

test('viewing the detail requires the manual-transactions.show permission', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['manual-transactions.list']);

    $manual = ManualTransaction::factory()->create(['company_id' => $company->id]);
    ManualTransactionLine::factory()->create(['manual_transaction_id' => $manual->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('manual-transactions.show', ['company' => $company->id, 'id' => $manual->id]))
        ->assertForbidden();
});
