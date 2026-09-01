<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\Company\Models\Company;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list only shows the collections of the active company', function () {
    [$user, $company, $client] = clientCollectionScenario();

    createClientCollection($user, $company, $client);

    $otherCompany = Company::create([
        'name' => 'Otra empresa',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    ClientCollection::factory()->create([
        'company_id' => $otherCompany->id,
        'client_id' => Client::factory()->create(['company_id' => $otherCompany->id])->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-collections.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client-collections/index')
            ->has('clientCollections', 1)
            ->where('meta.total', 1));
});

test('the list filters by client, status and payment method', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $other = Client::factory()->create(['company_id' => $company->id]);

    createClientCollection($user, $company, $client, ['payment_method' => 'transfer']);
    createClientCollection($user, $company, $other, ['payment_method' => 'cash']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-collections.index', [
            'company' => $company->id,
            'client_id' => $client->id,
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('clientCollections', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-collections.index', [
            'company' => $company->id,
            'payment_method' => 'cash',
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('clientCollections', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-collections.index', [
            'company' => $company->id,
            'status' => 'confirmed',
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('clientCollections', 0));
});

test('the list filters by collector and cheque status', function () {
    [$user, $company, $client] = clientCollectionScenario();

    createClientCollection($user, $company, $client, [
        'collected_by' => $user->id,
        'payment_method' => 'check',
        'check_number' => '00012345',
    ]);
    createClientCollection($user, $company, $client);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-collections.index', [
            'company' => $company->id,
            'collected_by' => $user->id,
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('clientCollections', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-collections.index', [
            'company' => $company->id,
            'check_status' => 'pending',
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('clientCollections', 1));
});

test('the list filters by date range', function () {
    [$user, $company, $client] = clientCollectionScenario();

    /** Un cobro con fecha vieja se valora con la tasa de su día, no con la de hoy. */
    ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => now()->subDays(10)->toDateString(),
        'rate' => 35.0,
        'type' => 'legal',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    createClientCollection($user, $company, $client, [
        'collection_date' => now()->subDays(10)->toDateString(),
    ]);
    createClientCollection($user, $company, $client);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-collections.index', [
            'company' => $company->id,
            'date_from' => now()->subDay()->toDateString(),
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('clientCollections', 1));
});

test('a user without permission cannot list the collections', function () {
    [$user, $company] = clientCollectionScenario();

    assignRoleWithPermissions($user, $company, ['clients.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-collections.index', ['company' => $company->id]))
        ->assertForbidden();
});
