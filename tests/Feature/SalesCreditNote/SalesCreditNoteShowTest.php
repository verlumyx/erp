<?php

declare(strict_types=1);

use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesCreditNote\Models\SalesCreditNoteLine;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the detail shows the note with its lines', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    $note = SalesCreditNote::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    SalesCreditNoteLine::factory()->create([
        'company_id' => $company->id,
        'sales_credit_note_id' => $note->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-credit-notes.show', ['company' => $company->id, 'id' => $note->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-credit-notes/show')
                ->where('salesCreditNote.id', $note->id)
                ->where('salesCreditNote.client_name', $client->name)
                ->has('salesCreditNote.lines', 1)
                ->where('salesCreditNote.lines.0.item_name', $item->name)
        );
});

test('the detail names the invoice the note corrects', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-credit-notes.show', ['company' => $company->id, 'id' => $note->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('salesCreditNote.sales_invoice_id', $invoice->id)
                ->where('salesCreditNote.sales_invoice_code', $invoice->code)
        );
});

test('a note from another company is not found', function () {
    [$user, $company] = salesCreditNoteScenario();

    $foreign = SalesCreditNote::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-credit-notes.show', ['company' => $company->id, 'id' => $foreign->id]))
        ->assertNotFound();
});

test('the edit form loads the note and its catalogs', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    $note = SalesCreditNote::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-credit-notes.edit', ['company' => $company->id, 'id' => $note->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-credit-notes/edit')
                ->where('salesCreditNote.id', $note->id)
                ->has('options.warehouses')
                ->missing('options.items')
                ->missing('options.clients')
                ->missing('options.salesInvoices')
        );
});

test('a user without permission cannot see the detail', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    $note = SalesCreditNote::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
    ]);

    assignRoleWithPermissions($user, $company, ['sales-credit-notes.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-credit-notes.show', ['company' => $company->id, 'id' => $note->id]))
        ->assertForbidden();
});
