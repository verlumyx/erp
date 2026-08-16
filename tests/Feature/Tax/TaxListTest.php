<?php

declare(strict_types=1);

use App\Modules\Tax\Models\Tax;

use function Pest\Laravel\actingAs;

test('the taxes index renders with taxes', function () {
    [$user, $company] = createUserWithCompany();

    Tax::factory()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('taxes.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('taxes/index')
        ->has('taxes', 3)
        ->where('meta.total', 3)
    );
});

test('taxes can be filtered by name', function () {
    [$user, $company] = createUserWithCompany();

    Tax::factory()->create(['company_id' => $company->id, 'name' => 'IVA 15%']);
    Tax::factory()->create(['company_id' => $company->id, 'name' => 'IGTF 3%']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('taxes.index', ['company' => $company->id, 'name' => 'IVA']));

    $response->assertInertia(fn ($page) => $page
        ->has('taxes', 1)
        ->where('taxes.0.name', 'IVA 15%')
    );
});

test('taxes can be filtered by code', function () {
    [$user, $company] = createUserWithCompany();

    $target = Tax::factory()->create(['company_id' => $company->id, 'code' => 'IMP000042']);
    Tax::factory()->create(['company_id' => $company->id, 'code' => 'IMP000099']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('taxes.index', ['company' => $company->id, 'code' => 'IMP000042']));

    $response->assertInertia(fn ($page) => $page
        ->has('taxes', 1)
        ->where('taxes.0.id', $target->id)
    );
});

test('taxes can be filtered by withholding', function () {
    [$user, $company] = createUserWithCompany();

    $target = Tax::factory()->withWithholding()->create(['company_id' => $company->id]);
    Tax::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('taxes.index', ['company' => $company->id, 'has_withholding' => 'yes']));

    $response->assertInertia(fn ($page) => $page
        ->has('taxes', 1)
        ->where('taxes.0.id', $target->id)
    );
});

test('taxes can be filtered by status', function () {
    [$user, $company] = createUserWithCompany();

    Tax::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    Tax::factory()->inactive()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('taxes.index', ['company' => $company->id, 'status' => 'inactive']));

    $response->assertInertia(fn ($page) => $page->has('taxes', 1));
});

test('tax filters combine with AND', function () {
    [$user, $company] = createUserWithCompany();

    $target = Tax::factory()->withWithholding()->create([
        'company_id' => $company->id,
        'name' => 'IVA general',
    ]);
    Tax::factory()->create([
        'company_id' => $company->id,
        'name' => 'IVA reducido',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('taxes.index', [
            'company' => $company->id,
            'name' => 'IVA',
            'has_withholding' => 'yes',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->has('taxes', 1)
        ->where('taxes.0.id', $target->id)
    );
});

test('the index only shows taxes from the active company', function () {
    [$user, $company] = createUserWithCompany();

    Tax::factory()->count(2)->create(['company_id' => $company->id]);
    Tax::factory()->count(3)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('taxes.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page
        ->has('taxes', 2)
        ->where('meta.total', 2)
    );
});

test('a user without permission cannot list taxes', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('taxes.index', ['company' => $company->id]));

    $response->assertForbidden();
});
