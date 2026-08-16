<?php

declare(strict_types=1);

use App\Modules\PriceList\Models\PriceList;

use function Pest\Laravel\actingAs;

test('the price lists index renders with price lists', function () {
    [$user, $company] = createUserWithCompany();

    PriceList::factory()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('price-lists.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('price-lists/index')
        ->has('priceLists', 3)
        ->where('meta.total', 3)
    );
});

test('price lists are sorted by name', function () {
    [$user, $company] = createUserWithCompany();

    PriceList::factory()->create(['company_id' => $company->id, 'name' => 'Corporativa']);
    PriceList::factory()->create(['company_id' => $company->id, 'name' => 'Aliados']);
    PriceList::factory()->create(['company_id' => $company->id, 'name' => 'Base']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('price-lists.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page
        ->where('priceLists.0.name', 'Aliados')
        ->where('priceLists.1.name', 'Base')
        ->where('priceLists.2.name', 'Corporativa')
    );
});

test('price lists can be filtered by name', function () {
    [$user, $company] = createUserWithCompany();

    PriceList::factory()->create(['company_id' => $company->id, 'name' => 'Mayorista enero']);
    PriceList::factory()->create(['company_id' => $company->id, 'name' => 'Detalle']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('price-lists.index', ['company' => $company->id, 'name' => 'Mayorista']));

    $response->assertInertia(fn ($page) => $page
        ->has('priceLists', 1)
        ->where('priceLists.0.name', 'Mayorista enero')
    );
});

test('price lists can be filtered by description', function () {
    [$user, $company] = createUserWithCompany();

    $target = PriceList::factory()->create([
        'company_id' => $company->id,
        'name' => 'Alfa',
        'description' => 'Clientes corporativos',
    ]);
    PriceList::factory()->create([
        'company_id' => $company->id,
        'name' => 'Beta',
        'description' => 'Clientes de mostrador',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('price-lists.index', ['company' => $company->id, 'description' => 'corporativos']));

    $response->assertInertia(fn ($page) => $page
        ->has('priceLists', 1)
        ->where('priceLists.0.id', $target->id)
    );
});

test('price lists can be filtered by code', function () {
    [$user, $company] = createUserWithCompany();

    $target = PriceList::factory()->create(['company_id' => $company->id, 'code' => 'PRL000042']);
    PriceList::factory()->create(['company_id' => $company->id, 'code' => 'PRL000099']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('price-lists.index', ['company' => $company->id, 'code' => 'PRL000042']));

    $response->assertInertia(fn ($page) => $page
        ->has('priceLists', 1)
        ->where('priceLists.0.id', $target->id)
    );
});

test('price lists can be filtered by status', function () {
    [$user, $company] = createUserWithCompany();

    PriceList::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    PriceList::factory()->inactive()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('price-lists.index', ['company' => $company->id, 'status' => 'inactive']));

    $response->assertInertia(fn ($page) => $page->has('priceLists', 1));
});

test('price list field filters combine with AND', function () {
    [$user, $company] = createUserWithCompany();

    $target = PriceList::factory()->create([
        'company_id' => $company->id,
        'name' => 'Mayorista enero',
        'description' => 'promocional',
    ]);
    PriceList::factory()->create([
        'company_id' => $company->id,
        'name' => 'Mayorista febrero',
        'description' => 'regular',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('price-lists.index', [
            'company' => $company->id,
            'name' => 'Mayorista',
            'description' => 'promocional',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->has('priceLists', 1)
        ->where('priceLists.0.id', $target->id)
    );
});

test('the index only shows price lists from the active company', function () {
    [$user, $company] = createUserWithCompany();

    PriceList::factory()->count(2)->create(['company_id' => $company->id]);
    PriceList::factory()->count(3)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('price-lists.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page
        ->has('priceLists', 2)
        ->where('meta.total', 2)
    );
});

test('a user without permission cannot list price lists', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('price-lists.index', ['company' => $company->id]));

    $response->assertForbidden();
});
