<?php

declare(strict_types=1);

use App\Modules\Role\Models\Role;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;

use function Pest\Laravel\actingAs;

test('a sales order in draft cannot be printed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = SalesOrder::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
        'status' => 'draft',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.pdf', ['company' => $company->id, 'id' => $order->id]))
        ->assertForbidden();
});

test('an approved sales order pdf can be generated and streamed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = SalesOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
        'approved_by' => $user->id,
        'subtotal' => 150,
        'tax_amount' => 24,
        'total' => 174,
    ]);

    SalesOrderLine::factory()->create([
        'company_id' => $company->id,
        'sales_order_id' => $order->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 3,
        'unit_price' => 50,
        'subtotal' => 150,
        'tax_percent' => 16,
        'tax_amount' => 24,
        'total' => 174,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.pdf', ['company' => $company->id, 'id' => $order->id]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain("orden-venta-{$order->code}.pdf");
});

test('a user without permission cannot download the sales order pdf', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = SalesOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    $role = Role::create([
        'company_id' => $company->id,
        'name' => 'No access '.uniqid(),
        'status' => 'active',
        'permission_type' => 'specific',
    ]);

    $user->companies()->updateExistingPivot($company->id, ['role_id' => $role->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.pdf', ['company' => $company->id, 'id' => $order->id]))
        ->assertForbidden();
});
