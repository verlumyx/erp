<?php

declare(strict_types=1);

use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Models\PurchaseReturnLine;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the detail shows the return with its lines', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $return = PurchaseReturn::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    PurchaseReturnLine::factory()->create([
        'company_id' => $company->id,
        'purchase_return_id' => $return->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.show', ['company' => $company->id, 'id' => $return->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('purchase-returns/show')
                ->where('purchaseReturn.id', $return->id)
                ->where('purchaseReturn.supplier_name', $supplier->name)
                ->where('purchaseReturn.warehouse_name', $warehouse->name)
                ->has('purchaseReturn.lines', 1)
                ->where('purchaseReturn.lines.0.item_name', $item->name)
        );
});

test('the detail names the invoice the return comes from', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.show', ['company' => $company->id, 'id' => $return->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('purchaseReturn.purchase_invoice_id', $invoice->id)
                ->where('purchaseReturn.purchase_invoice_code', $invoice->code)
                ->where('purchaseReturn.purchase_invoice_number', $invoice->supplier_invoice_number)
        );
});

test('the detail names the credit note that credited the return', function () {
    [$user, $company, $supplier, $warehouse] = purchaseReturnScenario();

    $note = PurchaseCreditNote::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
    ]);

    $return = PurchaseReturn::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'credit_note_id' => $note->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.show', ['company' => $company->id, 'id' => $return->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('purchaseReturn.credit_note_id', $note->id)
                ->where('purchaseReturn.credit_note_code', $note->code)
        );
});

test('a return from another company is not found', function () {
    [$user, $company] = purchaseReturnScenario();

    $foreign = PurchaseReturn::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.show', ['company' => $company->id, 'id' => $foreign->id]))
        ->assertNotFound();
});

test('the edit form loads the return and its catalogs', function () {
    [$user, $company, $supplier, $warehouse] = purchaseReturnScenario();

    $return = PurchaseReturn::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.edit', ['company' => $company->id, 'id' => $return->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('purchase-returns/edit')
                ->where('purchaseReturn.id', $return->id)
                ->has('options.warehouses')
                ->has('options.locations')
                ->missing('options.items')
                ->missing('options.suppliers')
                ->missing('options.purchaseInvoices')
        );
});

test('a user without permission cannot see the detail', function () {
    [$user, $company, $supplier, $warehouse] = purchaseReturnScenario();

    $return = PurchaseReturn::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    assignRoleWithPermissions($user, $company, ['purchase-returns.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.show', ['company' => $company->id, 'id' => $return->id]))
        ->assertForbidden();
});
