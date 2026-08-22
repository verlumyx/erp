<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the sales invoice list is rendered', function () {
    [$user, $company, $client, $warehouse] = salesInvoiceScenario();

    SalesInvoice::factory()->count(3)->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-invoices.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-invoices/index')
                ->has('salesInvoices', 3)
                ->where('meta.total', 3)
                ->has('options.warehouses')
                /* Artículos, clientes y pedidos se buscan contra su lookup. */
                ->missing('options.items')
                ->missing('options.clients')
                ->missing('options.salesOrders'),
        );
});

test('the list only shows invoices of the active company', function () {
    [$user, $company, $client, $warehouse] = salesInvoiceScenario();

    SalesInvoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    /** Factura de otra empresa: no debe aparecer. */
    SalesInvoice::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-invoices.index', ['company' => $company->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesInvoices', 1));
});

test('the list can be filtered by status and payment status', function () {
    [$user, $company, $client, $warehouse] = salesInvoiceScenario();

    SalesInvoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    SalesInvoice::factory()->confirmed()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'payment_status' => 'overdue',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-invoices.index', ['company' => $company->id, 'status' => 'confirmed']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesInvoices', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-invoices.index', ['company' => $company->id, 'payment_status' => 'overdue']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesInvoices', 1));
});

test('the list can be filtered by the client name', function () {
    [$user, $company, , $warehouse] = salesInvoiceScenario();

    $acme = Client::factory()->create(['company_id' => $company->id, 'name' => 'Acme Industrial']);
    $other = Client::factory()->create(['company_id' => $company->id, 'name' => 'Bodegón Central']);

    foreach ([$acme, $other] as $client) {
        SalesInvoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'warehouse_id' => $warehouse->id,
        ]);
    }

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-invoices.index', ['company' => $company->id, 'client' => 'Acme']))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('salesInvoices', 1)
                ->where('salesInvoices.0.client_name', 'Acme Industrial'),
        );
});

test('the list can be filtered by the invoice date range', function () {
    [$user, $company, $client, $warehouse] = salesInvoiceScenario();

    SalesInvoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->subDays(10)->toDateString(),
    ]);

    SalesInvoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->toDateString(),
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-invoices.index', [
            'company' => $company->id,
            'invoice_date_from' => now()->subDays(2)->toDateString(),
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesInvoices', 1));
});

test('a user without the list permission is rejected', function () {
    [$user, $company] = salesInvoiceScenario();

    assignRoleWithPermissions($user, $company, ['sales-orders.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-invoices.index', ['company' => $company->id]))
        ->assertForbidden();
});
