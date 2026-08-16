<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\SalesOrder\Models\SalesOrder;

use function Pest\Laravel\actingAs;

test('a draft sales order can be updated', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-orders.update', ['company' => $company->id, 'id' => $order->id]),
            salesOrderPayload($client, $warehouse, $item, $unit, [
                'client_reference' => 'OC-ACTUALIZADA',
                'lines' => [
                    [
                        'id' => $order->lines->first()->id,
                        'item_id' => $item->id,
                        'measurement_unit_id' => $unit->id,
                        'quantity' => 5,
                        'unit_price' => 80,
                    ],
                ],
            ]),
        );

    $response->assertRedirect(
        route('sales-orders.show', ['company' => $company->id, 'id' => $order->id]),
    );
    $response->assertSessionHasNoErrors();

    $updated = SalesOrder::with('lines')->find($order->id);
    expect($updated->client_reference)->toBe('OC-ACTUALIZADA');
    expect($updated->lines)->toHaveCount(1);
    expect((float) $updated->lines->first()->quantity)->toBe(5.0);
    expect((float) $updated->total)->toBe(400.0);
    /** El código no cambia al editar: es el identificador del documento. */
    expect($updated->code)->toBe($order->code);
});

test('a line that stops coming is deactivated, never deleted', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $second = Item::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $second->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 100],
            ['item_id' => $second->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 200],
        ],
    ]);

    $kept = $order->lines->firstWhere('item_id', $item->id);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-orders.update', ['company' => $company->id, 'id' => $order->id]),
            salesOrderPayload($client, $warehouse, $item, $unit, [
                'lines' => [
                    [
                        'id' => $kept->id,
                        'item_id' => $item->id,
                        'measurement_unit_id' => $unit->id,
                        'quantity' => 1,
                        'unit_price' => 100,
                    ],
                ],
            ]),
        )
        ->assertSessionHasNoErrors();

    $updated = SalesOrder::with('lines')->find($order->id);

    /** Las dos filas siguen ahí: la que salió del formulario quedó inactiva. */
    expect($updated->lines)->toHaveCount(2);
    expect($updated->lines->where('status', 'active'))->toHaveCount(1);
    expect($updated->lines->firstWhere('item_id', $second->id)->status)->toBe('inactive');

    /** Los totales suman solo las líneas activas. */
    expect((float) $updated->total)->toBe(100.0);
});

test('the active lines are renumbered from one', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $second = Item::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $second->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 100],
            ['item_id' => $second->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 200],
        ],
    ]);

    /** Se quita la primera: la segunda debe pasar a ser la línea 1. */
    $survivor = $order->lines->firstWhere('item_id', $second->id);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-orders.update', ['company' => $company->id, 'id' => $order->id]),
            salesOrderPayload($client, $warehouse, $item, $unit, [
                'lines' => [
                    [
                        'id' => $survivor->id,
                        'item_id' => $second->id,
                        'measurement_unit_id' => $unit->id,
                        'quantity' => 1,
                        'unit_price' => 200,
                    ],
                ],
            ]),
        )
        ->assertSessionHasNoErrors();

    $updated = SalesOrder::with('lines')->find($order->id);
    $active = $updated->lines->where('status', 'active');

    expect($active)->toHaveCount(1);
    expect($active->first()->line_number)->toBe(1);
    expect($active->first()->item_id)->toBe($second->id);
    /** La desactivada se va al final de la numeración y no colisiona. */
    expect($updated->lines->firstWhere('status', 'inactive')->line_number)->toBe(2);
});

test('a confirmed sales order cannot be updated', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-orders.update-status', ['company' => $company->id, 'id' => $order->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasNoErrors();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-orders.update', ['company' => $company->id, 'id' => $order->id]),
            salesOrderPayload($client, $warehouse, $item, $unit, [
                'client_reference' => 'NO-DEBE-GUARDARSE',
            ]),
        )
        ->assertSessionHasErrors('status');

    expect(SalesOrder::find($order->id)->client_reference)->not->toBe('NO-DEBE-GUARDARSE');
});

test('an order from another company cannot be updated', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();
    [, $otherCompany] = createUserWithCompany();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $otherCompany->id])
        ->put(
            route('sales-orders.update', ['company' => $otherCompany->id, 'id' => $order->id]),
            salesOrderPayload($client, $warehouse, $item, $unit),
        )
        ->assertForbidden();
});

test('a user without permission cannot update a sales order', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    assignRoleWithPermissions($user, $company, ['sales-orders.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-orders.update', ['company' => $company->id, 'id' => $order->id]),
            salesOrderPayload($client, $warehouse, $item, $unit),
        )
        ->assertForbidden();
});
