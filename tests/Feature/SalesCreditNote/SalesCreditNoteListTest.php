<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list only shows notes from the active company', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    SalesCreditNote::factory()->count(2)->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
    ]);

    SalesCreditNote::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-credit-notes.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-credit-notes/index')
                ->has('salesCreditNotes', 2)
                ->where('meta.total', 2)
        );
});

test('the list can be filtered by client, reason and status', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    $other = Client::factory()->create(['company_id' => $company->id]);

    SalesCreditNote::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'reason' => 'return',
        'status' => 'draft',
    ]);

    SalesCreditNote::factory()->confirmed()->create([
        'company_id' => $company->id,
        'client_id' => $other->id,
        'reason' => 'discount',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-credit-notes.index', ['company' => $company->id, 'client_id' => $other->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesCreditNotes', 1)->where('meta.total', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-credit-notes.index', ['company' => $company->id, 'reason' => 'discount']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesCreditNotes', 1)->where('meta.total', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-credit-notes.index', ['company' => $company->id, 'status' => 'draft']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesCreditNotes', 1)->where('meta.total', 1));
});

test('the list can be filtered by the fiscal number', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    SalesCreditNote::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'note_number' => '00424242',
    ]);

    SalesCreditNote::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'note_number' => '00999999',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-credit-notes.index', [
            'company' => $company->id,
            'note_number' => '424242',
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesCreditNotes', 1)->where('meta.total', 1));
});

test('the list can be filtered by the corrected invoice and by date', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    SalesCreditNote::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'sales_invoice_id' => $invoice->id,
        'note_date' => now()->toDateString(),
    ]);

    SalesCreditNote::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'note_date' => now()->subMonth()->toDateString(),
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-credit-notes.index', [
            'company' => $company->id,
            'sales_invoice_id' => $invoice->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesCreditNotes', 1)->where('meta.total', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-credit-notes.index', [
            'company' => $company->id,
            'date_from' => now()->subWeek()->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesCreditNotes', 1)->where('meta.total', 1));
});

test('the list names the client and the corrected invoice', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    SalesCreditNote::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'sales_invoice_id' => $invoice->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-credit-notes.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('salesCreditNotes.0.client_name', $client->name)
                ->where('salesCreditNotes.0.sales_invoice_code', $invoice->code)
        );
});

test('a user without permission cannot list sales credit notes', function () {
    [$user, $company] = salesCreditNoteScenario();
    assignRoleWithPermissions($user, $company, ['clients.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-credit-notes.index', ['company' => $company->id]))
        ->assertForbidden();
});
