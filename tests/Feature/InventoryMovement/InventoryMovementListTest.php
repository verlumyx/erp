<?php

declare(strict_types=1);

use App\Modules\InventoryMovement\Exceptions\InventoryMovementNotFoundException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\Item\Models\Item;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/**
 * Crea un movimiento ya cerrado dentro de la empresa dada, con su artículo,
 * bodega y ubicación. Va por la factory y no por el registrador porque estos
 * tests miran la pantalla, no el motor.
 *
 * @param  array<string, mixed>  $attributes
 */
function kardexRowFor(string $companyId, string $itemName, array $attributes = []): InventoryMovement
{
    $item = Item::factory()->create(['company_id' => $companyId, 'name' => $itemName]);
    $warehouse = Warehouse::factory()->create(['company_id' => $companyId]);
    $location = WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $companyId,
    ]);

    return InventoryMovement::factory()->create([
        'company_id' => $companyId,
        'item_id' => $item->id,
        'warehouse_id' => $warehouse->id,
        'location_id' => $location->id,
        ...$attributes,
    ]);
}

test('the list only shows movements of the active company', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();

    kardexRowFor($company->id, 'Artículo propio');
    kardexRowFor($otherCompany->id, 'Artículo ajeno');

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('inventory-movements.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('inventory-movements/index')
        ->has('movements', 1)
        ->where('movements.0.item_name', 'Artículo propio')
        ->where('meta.total', 1)
        ->has('warehouses')
    );
});

test('the list can be filtered by direction', function () {
    [$user, $company] = createUserWithCompany();

    kardexRowFor($company->id, 'Entrada', ['type' => 'in']);
    kardexRowFor($company->id, 'Salida', ['type' => 'out']);
    kardexRowFor($company->id, 'Traslado que sale', ['type' => 'transfer_out']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('inventory-movements.index', ['company' => $company->id, 'direction' => 'out']))
        ->assertInertia(fn ($page) => $page->has('movements', 2));
});

test('the list can be filtered by movement type', function () {
    [$user, $company] = createUserWithCompany();

    kardexRowFor($company->id, 'Ajuste', ['type' => 'adjustment_in']);
    kardexRowFor($company->id, 'Entrada', ['type' => 'in']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('inventory-movements.index', ['company' => $company->id, 'type' => 'adjustment_in']))
        ->assertInertia(fn ($page) => $page
            ->has('movements', 1)
            ->where('movements.0.item_name', 'Ajuste')
        );
});

test('the list can be filtered by the origin document', function () {
    [$user, $company] = createUserWithCompany();

    $originId = (string) Str::uuid7();

    kardexRowFor($company->id, 'De la factura', ['origin_type' => 'sales_invoice', 'origin_id' => $originId]);
    kardexRowFor($company->id, 'De otra cosa', ['origin_type' => 'entry']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('inventory-movements.index', [
            'company' => $company->id,
            'origin_type' => 'sales_invoice',
            'origin_id' => $originId,
        ]))
        ->assertInertia(fn ($page) => $page
            ->has('movements', 1)
            ->where('movements.0.item_name', 'De la factura')
        );
});

test('the list can be narrowed to a date range', function () {
    [$user, $company] = createUserWithCompany();

    kardexRowFor($company->id, 'Viejo', ['movement_date' => '2026-01-10 09:00:00']);
    kardexRowFor($company->id, 'Reciente', ['movement_date' => '2026-08-10 09:00:00']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('inventory-movements.index', [
            'company' => $company->id,
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
        ]))
        ->assertInertia(fn ($page) => $page
            ->has('movements', 1)
            ->where('movements.0.item_name', 'Reciente')
        );
});

test('the list can be searched by item or movement code', function () {
    [$user, $company] = createUserWithCompany();

    kardexRowFor($company->id, 'Taladro percutor');
    kardexRowFor($company->id, 'Cemento gris');

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('inventory-movements.index', ['company' => $company->id, 'q' => 'Taladro']))
        ->assertInertia(fn ($page) => $page
            ->has('movements', 1)
            ->where('movements.0.item_name', 'Taladro percutor')
        );
});

test('the most recent movement comes first', function () {
    [$user, $company] = createUserWithCompany();

    kardexRowFor($company->id, 'Viejo', ['movement_date' => '2026-02-01 09:00:00']);
    kardexRowFor($company->id, 'Nuevo', ['movement_date' => '2026-07-01 09:00:00']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('inventory-movements.index', ['company' => $company->id]))
        ->assertInertia(fn ($page) => $page->where('movements.0.item_name', 'Nuevo'));
});

test('the detail shows the movement of the active company', function () {
    [$user, $company] = createUserWithCompany();

    $movement = kardexRowFor($company->id, 'Taladro percutor', ['type' => 'in', 'quantity' => 7]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('inventory-movements.show', ['company' => $company->id, 'id' => $movement->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('inventory-movements/show')
            ->where('movement.id', $movement->id)
            ->where('movement.direction', 'in')
            ->where('movement.quantity', 7)
        );
});

test('showing a missing movement throws a not found exception', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('inventory-movements.show', ['company' => $company->id, 'id' => (string) Str::uuid7()]));
})->throws(InventoryMovementNotFoundException::class);

test('a movement of another company is not reachable', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();

    $foreign = kardexRowFor($otherCompany->id, 'Artículo ajeno');

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('inventory-movements.show', ['company' => $company->id, 'id' => $foreign->id]));
})->throws(InventoryMovementNotFoundException::class);

test('the kardex is not reachable without the list permission', function () {
    [$user, $company] = createUserWithCompany();

    assignRoleWithPermissions($user, $company, ['items.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('inventory-movements.index', ['company' => $company->id]))
        ->assertForbidden();
});

test('the detail is not reachable without the show permission', function () {
    [$user, $company] = createUserWithCompany();

    $movement = kardexRowFor($company->id, 'Taladro percutor');

    assignRoleWithPermissions($user, $company, ['inventory-movements.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('inventory-movements.show', ['company' => $company->id, 'id' => $movement->id]))
        ->assertForbidden();
});

test('there is no route to capture, edit or restate a movement', function () {
    expect(app('router')->getRoutes()->getByName('inventory-movements.create'))->toBeNull();
    expect(app('router')->getRoutes()->getByName('inventory-movements.store'))->toBeNull();
    expect(app('router')->getRoutes()->getByName('inventory-movements.edit'))->toBeNull();
    expect(app('router')->getRoutes()->getByName('inventory-movements.update'))->toBeNull();
    expect(app('router')->getRoutes()->getByName('inventory-movements.update-status'))->toBeNull();
});
