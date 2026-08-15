<?php

declare(strict_types=1);

use App\Modules\Transaction\Models\Transaction;

use function Pest\Laravel\actingAs;

test('the income-expenses report renders empty with the current-month range until a search is performed', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->count(3)->create([
        'company_id' => $company->id,
        'date' => now()->toDateString(),
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.income-expenses.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('reports/income-expenses/index')
        ->where('searched', false)
        ->where('filters.date_from', now()->startOfMonth()->toDateString())
        ->where('filters.date_to', now()->toDateString())
        ->has('movements', 0)
        ->where('meta.total', 0)
        ->where('summary.total_income', 0)
        ->where('summary.total_expense', 0)
        ->where('summary.balance', 0)
    );
});

test('the income-expenses report uses the current month as the default range once searched', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->create([
        'company_id' => $company->id,
        'type' => 'income',
        'category' => 'sale',
        'amount' => 100,
        'date' => now()->toDateString(),
    ]);
    Transaction::factory()->create([
        'company_id' => $company->id,
        'type' => 'expense',
        'category' => 'salary',
        'amount' => 30,
        'date' => now()->startOfMonth()->toDateString(),
    ]);
    Transaction::factory()->create([
        'company_id' => $company->id,
        'type' => 'income',
        'category' => 'sale',
        'amount' => 999,
        'date' => now()->startOfMonth()->subDay()->toDateString(),
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.income-expenses.index', ['company' => $company->id, 'searched' => '1']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('searched', true)
        ->where('filters.date_from', now()->startOfMonth()->toDateString())
        ->where('filters.date_to', now()->toDateString())
        ->has('movements', 2)
        ->where('summary.total_income', 100)
        ->where('summary.total_expense', 30)
        ->where('summary.balance', 70)
        ->where('summary.income_count', 1)
        ->where('summary.expense_count', 1)
    );
});

test('the income-expenses report can be filtered by an explicit date range', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->create(['company_id' => $company->id, 'date' => '2026-01-15']);
    Transaction::factory()->create(['company_id' => $company->id, 'date' => '2026-03-20']);
    Transaction::factory()->create(['company_id' => $company->id, 'date' => '2026-06-10']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.income-expenses.index', [
            'company' => $company->id,
            'date_from' => '2026-02-01',
            'date_to' => '2026-04-01',
            'searched' => '1',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->has('movements', 1)
        ->where('movements.0.date', '2026-03-20')
        ->where('meta.total', 1)
    );
});

test('the income-expenses summary totals reflect the selected range', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->create(['company_id' => $company->id, 'type' => 'income', 'category' => 'sale', 'amount' => 200, 'date' => '2026-03-05']);
    Transaction::factory()->create(['company_id' => $company->id, 'type' => 'income', 'category' => 'renewal', 'amount' => 50, 'date' => '2026-03-12']);
    Transaction::factory()->create(['company_id' => $company->id, 'type' => 'expense', 'category' => 'salary', 'amount' => 75, 'date' => '2026-03-20']);
    Transaction::factory()->create(['company_id' => $company->id, 'type' => 'income', 'category' => 'sale', 'amount' => 999, 'date' => '2026-05-01']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.income-expenses.index', [
            'company' => $company->id,
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
            'searched' => '1',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->where('summary.total_income', 250)
        ->where('summary.total_expense', 75)
        ->where('summary.balance', 175)
        ->where('summary.income_count', 2)
        ->where('summary.expense_count', 1)
    );
});

test('the income-expenses report only shows movements from the active company', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->count(2)->create(['company_id' => $company->id, 'date' => now()->toDateString()]);
    Transaction::factory()->count(3)->create(['date' => now()->toDateString()]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.income-expenses.index', ['company' => $company->id, 'searched' => '1']));

    $response->assertInertia(fn ($page) => $page
        ->has('movements', 2)
        ->where('meta.total', 2)
    );
});

test('a user without permission cannot view the income-expenses report', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.income-expenses.index', ['company' => $company->id]));

    $response->assertForbidden();
});
