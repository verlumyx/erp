<?php

declare(strict_types=1);

use App\Modules\Supplier\Models\Supplier;

use function Pest\Laravel\actingAs;

/**
 * Los dos endpoints de opciones que alimentan la pantalla de pagos: el select
 * de proveedores con deuda y el de facturas con saldo.
 */
test('the supplier lookup can offer only the ones that are owed money', function () {
    [$user, $company] = supplierPaymentScenario();

    $owed = Supplier::factory()->withBalance(500)->create([
        'company_id' => $company->id,
        'name' => 'Proveedor con deuda',
    ]);

    Supplier::factory()->create([
        'company_id' => $company->id,
        'name' => 'Proveedor al día',
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('suppliers.lookup', ['company' => $company->id, 'with_balance' => 'yes']));

    $response->assertOk();

    $values = array_column($response->json('data'), 'value');
    expect($values)->toBe([$owed->id]);
});

test('the supplier option carries the two indicators of the payment screen', function () {
    [$user, $company] = supplierPaymentScenario();

    $supplier = Supplier::factory()->withBalance(500)->create([
        'company_id' => $company->id,
        'advance_balance' => 120,
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('suppliers.lookup', ['company' => $company->id, 'ids' => $supplier->id]));

    $response->assertOk();
    expect((float) $response->json('data.0.meta.current_balance'))->toBe(500.0);
    expect((float) $response->json('data.0.meta.advance_balance'))->toBe(120.0);
});

test('the invoice lookup can offer only the ones that still owe something', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierPaymentScenario();

    $open = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    /** Una factura saldada por un pago confirmado ya no se ofrece. */
    $settled = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    $payment = createSupplierPayment($user, $company, $supplier, [
        'amount' => 250,
        'applications' => [
            ['purchase_invoice_id' => $settled->id, 'applied_amount' => 250],
        ],
    ]);

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertSessionHasNoErrors();

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-invoices.lookup', [
            'company' => $company->id,
            'supplier_id' => $supplier->id,
            'open' => 'yes',
        ]));

    $response->assertOk();

    $values = array_column($response->json('data'), 'value');
    expect($values)->toBe([$open->id]);
    expect((float) $response->json('data.0.meta.balance'))->toBe(250.0);
    expect((float) $response->json('data.0.meta.exchange_rate'))->toBe(36.5);
});

test('a user without permission cannot use the invoice lookup', function () {
    [$user, $company] = supplierPaymentScenario();

    assignRoleWithPermissions($user, $company, ['supplier-payments.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-invoices.lookup', ['company' => $company->id]))
        ->assertForbidden();
});
