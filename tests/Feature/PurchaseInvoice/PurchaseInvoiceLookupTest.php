<?php

declare(strict_types=1);

use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\Supplier\Models\Supplier;

use function Pest\Laravel\actingAs;

/** Una factura de la empresa activa, lista para ofrecerse en el select. */
function lookupPurchaseInvoice(
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    array $overrides = [],
): PurchaseInvoice {
    return PurchaseInvoice::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'confirmed',
        ...$overrides,
    ]);
}

test('the option carries the value, the label and the meta the form needs', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    PurchaseInvoice::where('id', $invoice->id)->update(['status' => 'confirmed']);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-invoices.lookup', ['company' => $company->id]));

    $response->assertOk();

    $option = $response->json('data.0');

    expect($option['value'])->toBe($invoice->id);
    expect($option['label'])->toBe("{$invoice->code} · {$invoice->supplier_invoice_number}");
    expect($option['meta']['supplier_id'])->toBe($supplier->id);
    expect($option['meta']['currency'])->toBe('USD');
    /** Las líneas viajan en el `meta`: de ellas sale lo que una nota acredita. */
    expect($option['meta']['lines'])->toHaveCount(1);
    expect($option['meta']['lines'][0]['id'])->toBe($invoice->lines->first()->id);
    expect($option['meta']['lines'][0]['item_id'])->toBe($item->id);
    expect($option['meta']['lines'][0]['item_name'])->toBe($item->name);
});

test('the search matches the code and the printed number of the supplier', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    $target = lookupPurchaseInvoice($company, $supplier, $warehouse, [
        'supplier_invoice_number' => '00-424242',
    ]);

    lookupPurchaseInvoice($company, $supplier, $warehouse, [
        'supplier_invoice_number' => '00-999999',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-invoices.lookup', ['company' => $company->id, 'q' => '424242']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', $target->id);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-invoices.lookup', ['company' => $company->id, 'q' => $target->code]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', $target->id);
});

test('the lookup can be narrowed to one supplier', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);

    lookupPurchaseInvoice($company, $supplier, $warehouse);
    $target = lookupPurchaseInvoice($company, $other, $warehouse);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-invoices.lookup', [
            'company' => $company->id,
            'supplier_id' => $other->id,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', $target->id);
});

test('searching only offers the invoices that can be credited', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    lookupPurchaseInvoice($company, $supplier, $warehouse, ['status' => 'draft']);
    lookupPurchaseInvoice($company, $supplier, $warehouse, ['status' => 'cancelled']);
    lookupPurchaseInvoice($company, $supplier, $warehouse, ['status' => 'confirmed']);
    lookupPurchaseInvoice($company, $supplier, $warehouse, ['status' => 'completed']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-invoices.lookup', ['company' => $company->id]))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('hydrating by ids does not filter by status', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    /** Una factura anulada después de emitir su nota sigue siendo la suya. */
    $cancelled = lookupPurchaseInvoice($company, $supplier, $warehouse, ['status' => 'cancelled']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-invoices.lookup', [
            'company' => $company->id,
            'ids' => $cancelled->id,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', $cancelled->id);
});

test('the lookup never leaves the active company', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    lookupPurchaseInvoice($company, $supplier, $warehouse);
    PurchaseInvoice::factory()->create(['status' => 'confirmed']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-invoices.lookup', ['company' => $company->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('the page size is capped by the backend and reports whether there is more', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    foreach (range(1, 3) as $ignored) {
        lookupPurchaseInvoice($company, $supplier, $warehouse);
    }

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-invoices.lookup', ['company' => $company->id, 'per_page' => 2]))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('has_more', true);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-invoices.lookup', ['company' => $company->id, 'per_page' => 2, 'page' => 2]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('has_more', false);

    /** Un `per_page` desmedido no puede convertirse en el volcado del catálogo. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-invoices.lookup', ['company' => $company->id, 'per_page' => 5000]))
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('a user without permission cannot use the lookup', function () {
    [$user, $company] = purchaseInvoiceScenario();
    assignRoleWithPermissions($user, $company, ['suppliers.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-invoices.lookup', ['company' => $company->id]))
        ->assertForbidden();
});
