<?php

declare(strict_types=1);

use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\Supplier\Models\Supplier;

use function Pest\Laravel\actingAs;

/**
 * El endpoint de opciones que consume `Select2Ajax`: lo usa la factura de
 * compra para elegir la orden que la origina.
 */
test('the lookup only offers the orders that can still be invoiced', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();

    $confirmed = PurchaseOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    /** En borrador todavía no compromete nada; anulada ya no admite factura. */
    PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'draft',
    ]);

    PurchaseOrder::factory()->cancelled()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-orders.lookup', ['company' => $company->id]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($confirmed->id);
    expect($response->json('data.0.meta.supplier_id'))->toBe($supplier->id);
    expect($response->json('data.0.meta.warehouse_id'))->toBe($warehouse->id);
});

test('the lookup can be narrowed to one supplier', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);

    PurchaseOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $wanted = PurchaseOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $other->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-orders.lookup', [
            'company' => $company->id,
            'supplier_id' => $other->id,
        ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($wanted->id);
});

test('the lookup searches by code', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();

    $wanted = PurchaseOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'code' => 'OCO009090',
    ]);

    PurchaseOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'code' => 'OCO001111',
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-orders.lookup', ['company' => $company->id, 'q' => '9090']));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($wanted->id);
});

/**
 * Hidratar lo ya elegido no filtra por estado: una orden completada después
 * sigue siendo el origen de su factura y su etiqueta tiene que resolverse.
 */
test('hydrating by ids ignores the status filter', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();

    $completed = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'completed',
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-orders.lookup', [
            'company' => $company->id,
            'ids' => $completed->id,
        ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($completed->id);
});

test('the lookup never leaves the active company', function () {
    [$user, $company] = purchaseOrderScenario();

    PurchaseOrder::factory()->confirmed()->create();

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-orders.lookup', ['company' => $company->id]));

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

test('a user without permission cannot use the lookup', function () {
    [$user, $company] = purchaseOrderScenario();
    assignRoleWithPermissions($user, $company, ['suppliers.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-orders.lookup', ['company' => $company->id]))
        ->assertForbidden();
});
