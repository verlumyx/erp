<?php

declare(strict_types=1);

use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;

use function Pest\Laravel\actingAs;

/**
 * @param  array<string, mixed>  $payload
 */
function putStatus(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    SalesOrder $order,
    array $payload,
): \Illuminate\Testing\TestResponse {
    return actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-orders.update-status', ['company' => $company->id, 'id' => $order->id]),
            $payload,
        );
}

test('a draft sales order can be confirmed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $response = putStatus($user, $company, $order, ['status' => 'confirmed']);

    $response->assertRedirect(
        route('sales-orders.show', ['company' => $company->id, 'id' => $order->id]),
    );
    $response->assertSessionHasNoErrors();

    $confirmed = SalesOrder::find($order->id);
    expect($confirmed->status)->toBe('confirmed');
    expect($confirmed->approved_by)->toBe($user->id);
    expect($confirmed->approved_at)->not->toBeNull();
});

test('a sales order can be cancelled with a reason', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    putStatus($user, $company, $order, [
        'status' => 'cancelled',
        'cancellation_reason' => 'El cliente desistió del pedido.',
    ])->assertSessionHasNoErrors();

    $cancelled = SalesOrder::find($order->id);
    expect($cancelled->status)->toBe('cancelled');
    expect($cancelled->cancelled_at)->not->toBeNull();
    expect($cancelled->cancellation_reason)->toBe('El cliente desistió del pedido.');
});

test('cancelling releases the stock reservation of every line', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    /** Simula la reserva que hará el confirmado cuando exista el kardex. */
    SalesOrderLine::where('sales_order_id', $order->id)->update(['reserved_quantity' => 2]);

    putStatus($user, $company, $order, ['status' => 'confirmed'])->assertSessionHasNoErrors();
    putStatus($user, $company, $order, [
        'status' => 'cancelled',
        'cancellation_reason' => 'Sin stock disponible.',
    ])->assertSessionHasNoErrors();

    expect((float) SalesOrderLine::where('sales_order_id', $order->id)->value('reserved_quantity'))
        ->toBe(0.0);
});

test('cancelling requires a reason', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    putStatus($user, $company, $order, ['status' => 'cancelled'])
        ->assertSessionHasErrors('cancellation_reason');

    expect(SalesOrder::find($order->id)->status)->toBe('draft');
});

test('a draft order cannot jump straight to completed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    putStatus($user, $company, $order, ['status' => 'completed'])
        ->assertSessionHasErrors('status');

    expect(SalesOrder::find($order->id)->status)->toBe('draft');
});

test('a cancelled order is final', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    putStatus($user, $company, $order, [
        'status' => 'cancelled',
        'cancellation_reason' => 'Duplicado.',
    ])->assertSessionHasNoErrors();

    putStatus($user, $company, $order, ['status' => 'confirmed'])
        ->assertSessionHasErrors('status');

    expect(SalesOrder::find($order->id)->status)->toBe('cancelled');
});

test('a confirmed order advances to partial and then to completed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    putStatus($user, $company, $order, ['status' => 'confirmed'])->assertSessionHasNoErrors();
    putStatus($user, $company, $order, ['status' => 'partial'])->assertSessionHasNoErrors();
    putStatus($user, $company, $order, ['status' => 'completed'])->assertSessionHasNoErrors();

    expect(SalesOrder::find($order->id)->status)->toBe('completed');
});

test('an order without active lines cannot be confirmed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    SalesOrderLine::where('sales_order_id', $order->id)->update(['status' => 'inactive']);

    putStatus($user, $company, $order, ['status' => 'confirmed'])
        ->assertSessionHasErrors('status');
});

test('a user without permission cannot change the status', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    assignRoleWithPermissions($user, $company, ['sales-orders.list']);

    putStatus($user, $company, $order, ['status' => 'confirmed'])->assertForbidden();
});
