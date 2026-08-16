<?php

declare(strict_types=1);

use App\Modules\User\Models\User;
use App\Modules\Warehouse\Exceptions\WarehouseNotFoundException;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('the warehouse show page renders', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->create(['company_id' => $company->id, 'name' => 'Bodega visible']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouses.show', ['company' => $company->id, 'id' => $warehouse->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('warehouses/show')
        ->where('warehouse.id', $warehouse->id)
        ->where('warehouse.name', 'Bodega visible')
    );
});

test('the show page exposes the responsible user name', function () {
    [$user, $company] = createUserWithCompany();

    $responsible = User::factory()->create(['name' => 'Ana Encargada']);
    $warehouse = Warehouse::factory()->create([
        'company_id' => $company->id,
        'responsible_user_id' => $responsible->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouses.show', ['company' => $company->id, 'id' => $warehouse->id]));

    $response->assertInertia(fn ($page) => $page->where('warehouse.responsible_user_name', 'Ana Encargada'));
});

test('the warehouse edit page renders with the responsible options', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouses.edit', ['company' => $company->id, 'id' => $warehouse->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('warehouses/edit')
        ->where('warehouse.id', $warehouse->id)
        ->has('users', 1)
        ->where('users.0.id', $user->id)
    );
});

test('the warehouse create page renders', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouses.create', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('warehouses/create')->has('users'));
});

test('showing a missing warehouse throws a not found exception', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('warehouses.show', ['company' => $company->id, 'id' => (string) Str::uuid7()]));
})->throws(WarehouseNotFoundException::class);

test('a warehouse from another company is not reachable', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = Warehouse::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('warehouses.show', ['company' => $company->id, 'id' => $foreign->id]));
})->throws(WarehouseNotFoundException::class);
