<?php

declare(strict_types=1);

use App\Modules\ClientType\Models\ClientType;

use function Pest\Laravel\actingAs;

test('the client types index renders with client types', function () {
    [$user, $company] = createUserWithCompany();

    ClientType::factory()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('client-types.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('client-types/index')
        ->has('clientTypes', 3)
        ->where('meta.total', 3)
    );
});

test('client types can be filtered by name', function () {
    [$user, $company] = createUserWithCompany();

    ClientType::factory()->create(['company_id' => $company->id, 'name' => 'Mayorista']);
    ClientType::factory()->create(['company_id' => $company->id, 'name' => 'Detalle']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('client-types.index', ['company' => $company->id, 'name' => 'Mayor']));

    $response->assertInertia(fn ($page) => $page
        ->has('clientTypes', 1)
        ->where('clientTypes.0.name', 'Mayorista')
    );
});

test('client types can be filtered by code', function () {
    [$user, $company] = createUserWithCompany();

    $target = ClientType::factory()->create(['company_id' => $company->id, 'code' => 'TCL000042']);
    ClientType::factory()->create(['company_id' => $company->id, 'code' => 'TCL000099']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('client-types.index', ['company' => $company->id, 'code' => 'TCL000042']));

    $response->assertInertia(fn ($page) => $page
        ->has('clientTypes', 1)
        ->where('clientTypes.0.id', $target->id)
    );
});

test('client types can be filtered by status', function () {
    [$user, $company] = createUserWithCompany();

    ClientType::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    ClientType::factory()->inactive()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('client-types.index', ['company' => $company->id, 'status' => 'inactive']));

    $response->assertInertia(fn ($page) => $page->has('clientTypes', 1));
});

test('client type filters combine with AND', function () {
    [$user, $company] = createUserWithCompany();

    $target = ClientType::factory()->create([
        'company_id' => $company->id,
        'name' => 'Cliente corporativo',
        'code' => 'TCL000010',
    ]);
    ClientType::factory()->create([
        'company_id' => $company->id,
        'name' => 'Cliente eventual',
        'code' => 'TCL000011',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('client-types.index', [
            'company' => $company->id,
            'name' => 'Cliente',
            'code' => 'TCL000010',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->has('clientTypes', 1)
        ->where('clientTypes.0.id', $target->id)
    );
});

test('the index only shows client types from the active company', function () {
    [$user, $company] = createUserWithCompany();

    ClientType::factory()->count(2)->create(['company_id' => $company->id]);
    ClientType::factory()->count(3)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('client-types.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page
        ->has('clientTypes', 2)
        ->where('meta.total', 2)
    );
});

test('a user without permission cannot list client types', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('client-types.index', ['company' => $company->id]));

    $response->assertForbidden();
});
