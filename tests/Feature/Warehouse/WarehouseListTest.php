<?php

declare(strict_types=1);

use App\Modules\Warehouse\Models\Warehouse;

use function Pest\Laravel\actingAs;

test('the warehouses index renders with warehouses', function () {
    [$user, $company] = createUserWithCompany();

    Warehouse::factory()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouses.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('warehouses/index')
        ->has('warehouses', 3)
        ->where('meta.total', 3)
    );
});

test('the default warehouse is listed first', function () {
    [$user, $company] = createUserWithCompany();

    Warehouse::factory()->create(['company_id' => $company->id, 'name' => 'Almacén norte']);
    $default = Warehouse::factory()->default()->create(['company_id' => $company->id, 'name' => 'Zeta central']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouses.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page->where('warehouses.0.id', $default->id));
});

test('warehouses can be filtered by name', function () {
    [$user, $company] = createUserWithCompany();

    Warehouse::factory()->create(['company_id' => $company->id, 'name' => 'Bodega Central']);
    Warehouse::factory()->create(['company_id' => $company->id, 'name' => 'Sucursal Este']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouses.index', ['company' => $company->id, 'name' => 'Central']));

    $response->assertInertia(fn ($page) => $page
        ->has('warehouses', 1)
        ->where('warehouses.0.name', 'Bodega Central')
    );
});

test('warehouses can be filtered by type', function () {
    [$user, $company] = createUserWithCompany();

    Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'main']);
    $transit = Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'transit']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouses.index', ['company' => $company->id, 'type' => 'transit']));

    $response->assertInertia(fn ($page) => $page
        ->has('warehouses', 1)
        ->where('warehouses.0.id', $transit->id)
    );
});

test('warehouses can be filtered by city and code', function () {
    [$user, $company] = createUserWithCompany();

    $target = Warehouse::factory()->create([
        'company_id' => $company->id,
        'city' => 'Valencia',
        'code' => 'BOD000042',
    ]);
    Warehouse::factory()->create(['company_id' => $company->id, 'city' => 'Maracaibo', 'code' => 'BOD000099']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouses.index', ['company' => $company->id, 'city' => 'Valencia']))
        ->assertInertia(fn ($page) => $page->has('warehouses', 1)->where('warehouses.0.id', $target->id));

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouses.index', ['company' => $company->id, 'code' => 'BOD000042']))
        ->assertInertia(fn ($page) => $page->has('warehouses', 1)->where('warehouses.0.id', $target->id));
});

test('warehouses can be filtered by status', function () {
    [$user, $company] = createUserWithCompany();

    Warehouse::factory()->create(['company_id' => $company->id]);
    Warehouse::factory()->inactive()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouses.index', ['company' => $company->id, 'status' => 'inactive']));

    $response->assertInertia(fn ($page) => $page->has('warehouses', 1));
});

test('the index only shows warehouses from the active company', function () {
    [$user, $company] = createUserWithCompany();

    Warehouse::factory()->count(2)->create(['company_id' => $company->id]);
    Warehouse::factory()->count(3)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouses.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page
        ->has('warehouses', 2)
        ->where('meta.total', 2)
    );
});

test('a user without permission cannot list warehouses', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouses.index', ['company' => $company->id]));

    $response->assertForbidden();
});
