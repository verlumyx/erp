<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\SalesReturn\Models\SalesReturn;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list only shows returns from the active company', function () {
    [$user, $company, $client, $warehouse] = salesReturnScenario();

    SalesReturn::factory()->count(2)->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    SalesReturn::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-returns/index')
                ->has('salesReturns', 2)
                ->where('meta.total', 2)
        );
});

test('the list can be filtered by client, reason and status', function () {
    [$user, $company, $client, $warehouse] = salesReturnScenario();

    $other = Client::factory()->create(['company_id' => $company->id]);

    SalesReturn::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'reason' => 'damaged',
        'status' => 'draft',
    ]);

    SalesReturn::factory()->confirmed()->create([
        'company_id' => $company->id,
        'client_id' => $other->id,
        'warehouse_id' => $warehouse->id,
        'reason' => 'excess',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.index', ['company' => $company->id, 'client_id' => $other->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesReturns', 1)->where('meta.total', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.index', ['company' => $company->id, 'reason' => 'excess']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesReturns', 1)->where('meta.total', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.index', ['company' => $company->id, 'status' => 'draft']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesReturns', 1)->where('meta.total', 1));
});

test('the list can be filtered by the condition of the goods', function () {
    [$user, $company, $client, $warehouse] = salesReturnScenario();

    SalesReturn::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'condition' => 'resalable',
    ]);

    SalesReturn::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'condition' => 'scrap',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.index', [
            'company' => $company->id,
            'condition' => 'scrap',
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesReturns', 1)->where('meta.total', 1));
});

test('the list can be filtered by the source invoice and by date', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    SalesReturn::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'sales_invoice_id' => $invoice->id,
        'return_date' => now()->toDateString(),
    ]);

    SalesReturn::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'return_date' => now()->subMonth()->toDateString(),
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.index', [
            'company' => $company->id,
            'sales_invoice_id' => $invoice->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesReturns', 1)->where('meta.total', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.index', [
            'company' => $company->id,
            'date_from' => now()->subWeek()->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesReturns', 1)->where('meta.total', 1));
});

test('the list names the client, the warehouse and the source invoice', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    SalesReturn::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'sales_invoice_id' => $invoice->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('salesReturns.0.client_name', $client->name)
                ->where('salesReturns.0.warehouse_name', $warehouse->name)
                ->where('salesReturns.0.sales_invoice_code', $invoice->code)
        );
});

test('a user without permission cannot list sales returns', function () {
    [$user, $company] = salesReturnScenario();
    assignRoleWithPermissions($user, $company, ['clients.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-returns.index', ['company' => $company->id]))
        ->assertForbidden();
});
