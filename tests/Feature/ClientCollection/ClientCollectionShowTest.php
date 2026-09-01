<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\Company\Models\Company;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the detail carries the collection with its distribution', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $collection = createClientCollection($user, $company, $client, [
        'amount' => 200,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-collections.show', ['company' => $company->id, 'id' => $collection->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client-collections/show')
            ->where('clientCollection.code', 'COB000001')
            ->where('clientCollection.client_name', $client->name)
            ->has('clientCollection.applications', 1)
            ->where('clientCollection.applications.0.sales_invoice_code', $invoice->code));
});

test('a collection of another company is not reachable', function () {
    [$user, $company] = clientCollectionScenario();

    $otherCompany = Company::create([
        'name' => 'Otra empresa',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $collection = ClientCollection::factory()->create([
        'company_id' => $otherCompany->id,
        'client_id' => Client::factory()->create(['company_id' => $otherCompany->id])->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-collections.show', ['company' => $company->id, 'id' => $collection->id]))
        ->assertNotFound();
});

test('the edit form is rendered for a draft collection', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-collections.edit', ['company' => $company->id, 'id' => $collection->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client-collections/edit')
            ->where('clientCollection.id', $collection->id));
});

test('a user without permission cannot see the detail', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client);

    assignRoleWithPermissions($user, $company, ['client-collections.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-collections.show', ['company' => $company->id, 'id' => $collection->id]))
        ->assertForbidden();
});
