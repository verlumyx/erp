<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('a sales invoice detail is rendered with its lines', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-invoices.show', ['company' => $company->id, 'id' => $invoice->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-invoices/show')
                ->where('salesInvoice.id', $invoice->id)
                ->where('salesInvoice.code', 'FVE000001')
                ->where('salesInvoice.status', 'draft')
                ->where('salesInvoice.client_name', $client->name)
                ->where('salesInvoice.warehouse_name', $warehouse->name)
                ->has('salesInvoice.lines', 1)
                ->where('salesInvoice.lines.0.item_name', $item->name),
        );
});

test('the edit form is rendered for a draft invoice', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-invoices.edit', ['company' => $company->id, 'id' => $invoice->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-invoices/edit')
                ->where('salesInvoice.id', $invoice->id)
                ->missing('options.items'),
        );
});

test('the create form is rendered with its catalogs', function () {
    [$user, $company] = salesInvoiceScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-invoices.create', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-invoices/create')
                ->has('options.warehouses', 1)
                ->has('options.taxes')
                /* Artículos, clientes y pedidos se buscan contra su lookup. */
                ->missing('options.items')
                ->missing('options.clients')
                ->missing('options.salesOrders'),
        );
});

test('an invoice of another company is not reachable', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();
    [, $otherCompany] = createUserWithCompany();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $otherCompany->id])
        ->get(route('sales-invoices.show', ['company' => $otherCompany->id, 'id' => $invoice->id]))
        ->assertForbidden();
});

test('a user without permission cannot see the detail', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    assignRoleWithPermissions($user, $company, ['sales-invoices.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-invoices.show', ['company' => $company->id, 'id' => $invoice->id]))
        ->assertForbidden();
});

test('a missing sales invoice returns a not found response', function () {
    [$user, $company] = salesInvoiceScenario();

    /** `SalesInvoiceNotFoundException` la traduce a 404 el handler de bootstrap/app.php. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-invoices.show', [
            'company' => $company->id,
            'id' => (string) Str::uuid7(),
        ]))
        ->assertNotFound();
});
