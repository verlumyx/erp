<?php

declare(strict_types=1);

use App\Modules\ManualTransaction\Models\ManualTransaction;
use App\Modules\ManualTransaction\Models\ManualTransactionLine;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('the index page renders the manual transactions list', function () {
    [$user, $company] = createUserWithCompany();
    ManualTransaction::factory()->count(2)->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('manual-transactions.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('manual-transactions/index')
            ->has('manualTransactions', 2)
            ->where('meta.total', 2)
        );
});

test('the show page renders the header with its lines', function () {
    [$user, $company] = createUserWithCompany();
    $manual = ManualTransaction::factory()->create(['company_id' => $company->id]);
    ManualTransactionLine::factory()->count(3)->create(['manual_transaction_id' => $manual->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('manual-transactions.show', ['company' => $company->id, 'id' => $manual->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('manual-transactions/show')
            ->where('manualTransaction.id', $manual->id)
            ->has('manualTransaction.lines', 3)
        );
});

test('a manual transaction is scoped to its company', function () {
    [, $companyA] = createUserWithCompany();
    [$userB, $companyB] = createUserWithCompany();

    $manual = ManualTransaction::factory()->create(['company_id' => $companyA->id]);

    actingAs($userB)
        ->withSession(['current_company_id' => $companyB->id])
        ->get(route('manual-transactions.show', ['company' => $companyB->id, 'id' => $manual->id]))
        ->assertNotFound();
});
