<?php

declare(strict_types=1);

use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the detail shows the invoice with its lines', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $invoice = PurchaseInvoice::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    PurchaseInvoiceLine::factory()->create([
        'company_id' => $company->id,
        'purchase_invoice_id' => $invoice->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-invoices.show', ['company' => $company->id, 'id' => $invoice->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('purchase-invoices/show')
                ->where('purchaseInvoice.id', $invoice->id)
                ->where('purchaseInvoice.supplier_name', $supplier->name)
                ->where('purchaseInvoice.warehouse_name', $warehouse->name)
                ->has('purchaseInvoice.lines', 1)
                ->where('purchaseInvoice.lines.0.item_name', $item->name)
        );
});

test('the detail names the source document of the invoice', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-invoices.show', ['company' => $company->id, 'id' => $invoice->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('purchaseInvoice.sourceable_type', 'purchase_order')
                ->where('purchaseInvoice.sourceable_id', $order->id)
                ->where('purchaseInvoice.sourceable_code', $order->code)
        );
});

test('an invoice from another company is not found', function () {
    [$user, $company] = purchaseInvoiceScenario();

    $foreign = PurchaseInvoice::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-invoices.show', ['company' => $company->id, 'id' => $foreign->id]))
        ->assertNotFound();
});

test('the edit form loads the invoice and its catalogs', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    $invoice = PurchaseInvoice::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-invoices.edit', ['company' => $company->id, 'id' => $invoice->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('purchase-invoices/edit')
                ->where('purchaseInvoice.id', $invoice->id)
                ->has('options.warehouses')
                ->missing('options.items')
                ->missing('options.suppliers')
        );
});

test('a user without permission cannot see the detail', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    $invoice = PurchaseInvoice::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    assignRoleWithPermissions($user, $company, ['purchase-invoices.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-invoices.show', ['company' => $company->id, 'id' => $invoice->id]))
        ->assertForbidden();
});
