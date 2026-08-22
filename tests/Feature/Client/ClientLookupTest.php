<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\PriceList\Models\PriceList;

use function Pest\Laravel\actingAs;

test('the lookup endpoint returns clients as select options', function () {
    [$user, $company] = createUserWithCompany();

    $priceList = PriceList::factory()->create(['company_id' => $company->id]);
    $client = Client::factory()->create([
        'company_id' => $company->id,
        'code' => 'CLI000001',
        'name' => 'Bodegón La Esquina',
        'price_list_id' => $priceList->id,
        'salesperson_id' => $user->id,
        'payment_term_days' => 15,
        'credit_blocked' => 'no',
        'status' => 'active',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('clients.lookup', ['company' => $company->id]));

    $response->assertOk();
    $response->assertJsonPath('data.0.value', $client->id);
    $response->assertJsonPath('data.0.label', 'CLI000001 — Bodegón La Esquina');
    $response->assertJsonPath('data.0.meta.price_list_id', $priceList->id);
    $response->assertJsonPath('data.0.meta.salesperson_id', $user->id);
    $response->assertJsonPath('data.0.meta.payment_term_days', 15);
    $response->assertJsonPath('data.0.meta.credit_blocked', 'no');
    $response->assertJsonPath('has_more', false);
});

test('each option carries the delivery addresses the order header needs', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $shipping = ClientAddress::factory()->default()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'name' => 'Depósito Norte',
    ]);
    ClientAddress::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => 'inactive',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('clients.lookup', ['company' => $company->id]));

    $response->assertOk();
    $response->assertJsonCount(1, 'data.0.meta.addresses');
    $response->assertJsonPath('data.0.meta.addresses.0.id', $shipping->id);
    $response->assertJsonPath('data.0.meta.addresses.0.name', 'Depósito Norte');
    $response->assertJsonPath('data.0.meta.addresses.0.is_default', 'yes');
});

test('the lookup endpoint searches by name, legal name, code and document', function (string $term) {
    [$user, $company] = createUserWithCompany();

    Client::factory()->create([
        'company_id' => $company->id,
        'code' => 'CLI000009',
        'name' => 'Supermercado Aragua',
        'legal_name' => 'Inversiones Aragua C.A.',
        'document_number' => '123456789',
        'status' => 'active',
    ]);
    Client::factory()->create([
        'company_id' => $company->id,
        'code' => 'CLI000010',
        'name' => 'Otro Cliente',
        'legal_name' => 'Otro Cliente C.A.',
        'document_number' => '987654321',
        'status' => 'active',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('clients.lookup', ['company' => $company->id, 'q' => $term]));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.label', 'CLI000009 — Supermercado Aragua');
})->with(['Aragua', 'Inversiones', 'CLI000009', '123456789']);

test('the lookup endpoint only returns active clients of the active company', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    Client::factory()->create(['company_id' => $company->id, 'status' => 'inactive']);
    Client::factory()->create(['status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('clients.lookup', ['company' => $company->id]));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

test('the lookup endpoint paginates and reports whether more pages remain', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->count(5)->create(['company_id' => $company->id, 'status' => 'active']);

    $first = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('clients.lookup', [
            'company' => $company->id,
            'per_page' => 2,
            'page' => 1,
        ]));

    $first->assertOk();
    $first->assertJsonCount(2, 'data');
    $first->assertJsonPath('has_more', true);

    $last = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('clients.lookup', [
            'company' => $company->id,
            'per_page' => 2,
            'page' => 3,
        ]));

    $last->assertOk();
    $last->assertJsonCount(1, 'data');
    $last->assertJsonPath('has_more', false);
});

test('the lookup endpoint caps how many options a single page can ask for', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->count(55)->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('clients.lookup', ['company' => $company->id, 'per_page' => 500]));

    $response->assertOk();
    $response->assertJsonCount(50, 'data');
});

test('hydrating by id also resolves a client deactivated after being chosen', function () {
    [$user, $company] = createUserWithCompany();

    $chosen = Client::factory()->create(['company_id' => $company->id, 'status' => 'inactive']);
    Client::factory()->count(3)->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('clients.lookup', ['company' => $company->id, 'ids' => $chosen->id]));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.value', $chosen->id);
});

test('a user without permission cannot look clients up', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['sales-orders.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('clients.lookup', ['company' => $company->id]))
        ->assertForbidden();
});
