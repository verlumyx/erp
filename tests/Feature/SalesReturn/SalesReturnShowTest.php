<?php

declare(strict_types=1);

use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SalesReturn\Models\SalesReturnLine;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the detail shows the return with its lines', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $return = SalesReturn::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    SalesReturnLine::factory()->create([
        'company_id' => $company->id,
        'sales_return_id' => $return->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.show', ['company' => $company->id, 'id' => $return->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-returns/show')
                ->where('salesReturn.id', $return->id)
                ->where('salesReturn.client_name', $client->name)
                ->where('salesReturn.warehouse_name', $warehouse->name)
                ->has('salesReturn.lines', 1)
                ->where('salesReturn.lines.0.item_name', $item->name)
        );
});

test('the detail names the invoice the return comes from', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.show', ['company' => $company->id, 'id' => $return->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('salesReturn.sales_invoice_id', $invoice->id)
                ->where('salesReturn.sales_invoice_code', $invoice->code)
                ->where('salesReturn.sales_invoice_number', $invoice->client_invoice_number)
        );
});

test('the detail names the credit note that credited the return', function () {
    [$user, $company, $client, $warehouse] = salesReturnScenario();

    $note = SalesCreditNote::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
    ]);

    $return = SalesReturn::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'credit_note_id' => $note->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.show', ['company' => $company->id, 'id' => $return->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('salesReturn.credit_note_id', $note->id)
                ->where('salesReturn.credit_note_code', $note->code)
        );
});

test('a return from another company is not found', function () {
    [$user, $company] = salesReturnScenario();

    $foreign = SalesReturn::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.show', ['company' => $company->id, 'id' => $foreign->id]))
        ->assertNotFound();
});

test('the edit form loads the return and its catalogs', function () {
    [$user, $company, $client, $warehouse] = salesReturnScenario();

    $return = SalesReturn::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.edit', ['company' => $company->id, 'id' => $return->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-returns/edit')
                ->where('salesReturn.id', $return->id)
                ->has('options.warehouses')
                ->missing('options.locations')
                ->missing('options.taxes')
                ->missing('options.items')
                ->missing('options.clients')
                ->missing('options.salesInvoices')
        );
});

test('a user without permission cannot see the detail', function () {
    [$user, $company, $client, $warehouse] = salesReturnScenario();

    $return = SalesReturn::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    assignRoleWithPermissions($user, $company, ['sales-returns.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.show', ['company' => $company->id, 'id' => $return->id]))
        ->assertForbidden();
});
