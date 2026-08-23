<?php

declare(strict_types=1);

use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\Supplier\Models\Supplier;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list only shows notes from the active company', function () {
    [$user, $company, $supplier] = purchaseCreditNoteScenario();

    PurchaseCreditNote::factory()->count(2)->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
    ]);

    PurchaseCreditNote::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-credit-notes.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('purchase-credit-notes/index')
                ->has('purchaseCreditNotes', 2)
                ->where('meta.total', 2)
        );
});

test('the list can be filtered by supplier, reason and status', function () {
    [$user, $company, $supplier] = purchaseCreditNoteScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);

    PurchaseCreditNote::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'reason' => 'return',
        'status' => 'draft',
    ]);

    PurchaseCreditNote::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $other->id,
        'reason' => 'discount',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-credit-notes.index', ['company' => $company->id, 'supplier_id' => $other->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseCreditNotes', 1)->where('meta.total', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-credit-notes.index', ['company' => $company->id, 'reason' => 'discount']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseCreditNotes', 1)->where('meta.total', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-credit-notes.index', ['company' => $company->id, 'status' => 'draft']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseCreditNotes', 1)->where('meta.total', 1));
});

test('the list can be filtered by the document number of the supplier', function () {
    [$user, $company, $supplier] = purchaseCreditNoteScenario();

    PurchaseCreditNote::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'supplier_document_number' => 'NC-424242',
    ]);

    PurchaseCreditNote::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'supplier_document_number' => 'NC-999999',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-credit-notes.index', [
            'company' => $company->id,
            'supplier_document_number' => '424242',
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseCreditNotes', 1)->where('meta.total', 1));
});

test('the list can be filtered by the corrected invoice and by date', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseCreditNoteScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    PurchaseCreditNote::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'purchase_invoice_id' => $invoice->id,
        'note_date' => now()->toDateString(),
    ]);

    PurchaseCreditNote::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'note_date' => now()->subMonth()->toDateString(),
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-credit-notes.index', [
            'company' => $company->id,
            'purchase_invoice_id' => $invoice->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseCreditNotes', 1)->where('meta.total', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-credit-notes.index', [
            'company' => $company->id,
            'date_from' => now()->subWeek()->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseCreditNotes', 1)->where('meta.total', 1));
});

test('the list names the supplier and the corrected invoice', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseCreditNoteScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    PurchaseCreditNote::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'purchase_invoice_id' => $invoice->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-credit-notes.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('purchaseCreditNotes.0.supplier_name', $supplier->name)
                ->where('purchaseCreditNotes.0.purchase_invoice_code', $invoice->code)
        );
});

test('a user without permission cannot list purchase credit notes', function () {
    [$user, $company] = purchaseCreditNoteScenario();
    assignRoleWithPermissions($user, $company, ['suppliers.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-credit-notes.index', ['company' => $company->id]))
        ->assertForbidden();
});
