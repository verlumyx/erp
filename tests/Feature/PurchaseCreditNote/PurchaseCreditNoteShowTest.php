<?php

declare(strict_types=1);

use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNoteLine;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the detail shows the note with its lines', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $note = PurchaseCreditNote::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'created_by' => $user->id,
    ]);

    PurchaseCreditNoteLine::factory()->create([
        'company_id' => $company->id,
        'purchase_credit_note_id' => $note->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-credit-notes.show', ['company' => $company->id, 'id' => $note->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('purchase-credit-notes/show')
                ->where('purchaseCreditNote.id', $note->id)
                ->where('purchaseCreditNote.supplier_name', $supplier->name)
                ->has('purchaseCreditNote.lines', 1)
                ->where('purchaseCreditNote.lines.0.item_name', $item->name)
        );
});

test('the detail names the invoice the note corrects', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseCreditNoteScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-credit-notes.show', ['company' => $company->id, 'id' => $note->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('purchaseCreditNote.purchase_invoice_id', $invoice->id)
                ->where('purchaseCreditNote.purchase_invoice_code', $invoice->code)
                ->where('purchaseCreditNote.purchase_invoice_number', $invoice->supplier_invoice_number)
        );
});

test('a note from another company is not found', function () {
    [$user, $company] = purchaseCreditNoteScenario();

    $foreign = PurchaseCreditNote::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-credit-notes.show', ['company' => $company->id, 'id' => $foreign->id]))
        ->assertNotFound();
});

test('the edit form loads the note and its catalogs', function () {
    [$user, $company, $supplier] = purchaseCreditNoteScenario();

    $note = PurchaseCreditNote::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-credit-notes.edit', ['company' => $company->id, 'id' => $note->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('purchase-credit-notes/edit')
                ->where('purchaseCreditNote.id', $note->id)
                ->has('options.warehouses')
                ->missing('options.items')
                ->missing('options.suppliers')
                ->missing('options.purchaseInvoices')
        );
});

test('a user without permission cannot see the detail', function () {
    [$user, $company, $supplier] = purchaseCreditNoteScenario();

    $note = PurchaseCreditNote::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
    ]);

    assignRoleWithPermissions($user, $company, ['purchase-credit-notes.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-credit-notes.show', ['company' => $company->id, 'id' => $note->id]))
        ->assertForbidden();
});
