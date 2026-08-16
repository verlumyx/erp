<?php

declare(strict_types=1);

use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Exceptions\WarehouseLocationNotFoundException;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('the location show page renders with its warehouse name', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create([
        'company_id' => $company->id,
        'name' => 'Bodega con ubicaciones',
    ]);
    $location = WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
        'name' => 'Estante visible',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouse-locations.show', ['company' => $company->id, 'id' => $location->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('warehouse-locations/show')
        ->where('location.id', $location->id)
        ->where('location.name', 'Estante visible')
        ->where('location.warehouse_name', 'Bodega con ubicaciones')
    );
});

test('the location create page renders with the select options', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id, 'company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouse-locations.create', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('warehouse-locations/create')
        ->has('warehouses', 1)
        ->has('parents', 1)
    );
});

test('the location edit page renders', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $location = WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouse-locations.edit', ['company' => $company->id, 'id' => $location->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('warehouse-locations/edit')
        ->where('location.id', $location->id)
        ->has('warehouses')
    );
});

test('showing a missing location throws a not found exception', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('warehouse-locations.show', ['company' => $company->id, 'id' => (string) Str::uuid7()]));
})->throws(WarehouseLocationNotFoundException::class);

test('a location from another company is not reachable', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = WarehouseLocation::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('warehouse-locations.show', ['company' => $company->id, 'id' => $foreign->id]));
})->throws(WarehouseLocationNotFoundException::class);
