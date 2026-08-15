<?php

declare(strict_types=1);

use App\Modules\Transaction\Models\Transaction;

use function Pest\Laravel\actingAs;

test('the movements report renders empty until a search is performed', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.movements.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('reports/movements/index')
        ->where('searched', false)
        ->has('movements', 0)
        ->where('meta.total', 0)
    );
});

test('the movements report renders with movements once searched', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.movements.index', ['company' => $company->id, 'searched' => '1']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('reports/movements/index')
        ->where('searched', true)
        ->has('movements', 3)
        ->where('meta.total', 3)
    );
});

test('the report only shows movements from the active company', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->count(2)->create(['company_id' => $company->id]);
    Transaction::factory()->count(3)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.movements.index', ['company' => $company->id, 'searched' => '1']));

    $response->assertInertia(fn ($page) => $page
        ->has('movements', 2)
        ->where('meta.total', 2)
    );
});

test('movements can be filtered by type', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->income()->count(2)->create(['company_id' => $company->id]);
    Transaction::factory()->expense()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.movements.index', ['company' => $company->id, 'type' => 'income', 'searched' => '1']));

    $response->assertInertia(fn ($page) => $page
        ->has('movements', 2)
        ->where('meta.total', 2)
    );
});

test('movements can be filtered by category', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->create(['company_id' => $company->id, 'type' => 'expense', 'category' => 'salary']);
    Transaction::factory()->create(['company_id' => $company->id, 'type' => 'expense', 'category' => 'marketing']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.movements.index', ['company' => $company->id, 'category' => 'salary', 'searched' => '1']));

    $response->assertInertia(fn ($page) => $page
        ->has('movements', 1)
        ->where('movements.0.category', 'salary')
    );
});

test('movements can be filtered by date range', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->create(['company_id' => $company->id, 'date' => '2026-01-15']);
    Transaction::factory()->create(['company_id' => $company->id, 'date' => '2026-03-20']);
    Transaction::factory()->create(['company_id' => $company->id, 'date' => '2026-06-10']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.movements.index', [
            'company' => $company->id,
            'date_from' => '2026-02-01',
            'date_to' => '2026-04-01',
            'searched' => '1',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->has('movements', 1)
        ->where('movements.0.date', '2026-03-20')
    );
});

test('the movements report paginates results', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->count(25)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.movements.index', ['company' => $company->id, 'limit' => 20, 'searched' => '1']));

    $response->assertInertia(fn ($page) => $page
        ->has('movements', 20)
        ->where('meta.total', 25)
        ->where('meta.has_more', true)
    );
});

test('a user without permission cannot view the movements report', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.movements.index', ['company' => $company->id]));

    $response->assertForbidden();
});
