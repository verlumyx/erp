<?php

use App\Modules\Client\Models\Client;
use App\Modules\Store\Models\StoreCustomer;

use function Pest\Laravel\actingAs;

test('vincular manualmente guarda el cliente, quién y cómo', function () {
    $scenario = storeOrderScenario();

    $client = Client::factory()->create(['company_id' => $scenario['company']->id]);
    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->put(route('store-customers.link', ['company' => $scenario['company']->id, 'id' => $customer->id]), [
            'client_id' => $client->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('store-customers.show', ['company' => $scenario['company']->id, 'id' => $customer->id]));

    $customer->refresh();

    expect($customer->client_id)->toBe($client->id)
        ->and($customer->link_source)->toBe('manual')
        ->and($customer->linked_by)->toBe($scenario['user']->id)
        ->and($customer->linked_at)->not->toBeNull();
});

test('un comprador ya vinculado no se vuelve a vincular', function () {
    $scenario = storeOrderScenario();

    $first = Client::factory()->create(['company_id' => $scenario['company']->id]);
    $second = Client::factory()->create(['company_id' => $scenario['company']->id]);
    $customer = StoreCustomer::factory()->linkedTo($first->id)->create(['company_id' => $scenario['company']->id]);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->from('/')
        ->put(route('store-customers.link', ['company' => $scenario['company']->id, 'id' => $customer->id]), [
            'client_id' => $second->id,
        ])
        ->assertSessionHasErrors(['client_id']);

    expect($customer->fresh()->client_id)->toBe($first->id);
});

test('un cliente vinculado a otro comprador no se vincula de nuevo', function () {
    $scenario = storeOrderScenario();

    $client = Client::factory()->create(['company_id' => $scenario['company']->id]);
    StoreCustomer::factory()->linkedTo($client->id)->create(['company_id' => $scenario['company']->id]);
    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->from('/')
        ->put(route('store-customers.link', ['company' => $scenario['company']->id, 'id' => $customer->id]), [
            'client_id' => $client->id,
        ])
        ->assertSessionHasErrors(['client_id']);

    expect($customer->fresh()->client_id)->toBeNull();
});

test('un cliente de otra empresa no se vincula', function () {
    $scenario = storeOrderScenario();
    [, $otherCompany] = createUserWithCompany();

    $client = Client::factory()->create(['company_id' => $otherCompany->id]);
    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->from('/')
        ->put(route('store-customers.link', ['company' => $scenario['company']->id, 'id' => $customer->id]), [
            'client_id' => $client->id,
        ])
        ->assertSessionHasErrors(['client_id']);
});

test('bloquear un comprador revoca sus tokens', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);
    storeCustomerToken($customer);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->from('/')
        ->put(route('store-customers.update-status', ['company' => $scenario['company']->id, 'id' => $customer->id]), [
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors();

    expect($customer->fresh()->status)->toBe('inactive')
        ->and($customer->tokens()->count())->toBe(0);
});

test('el listado y el detalle de compradores se renderizan con sus props', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->get(route('store-customers.index', ['company' => $scenario['company']->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('store/customers/index')
            ->has('store_customers', 1)
            ->where('store_customers.0.id', $customer->id));

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->get(route('store-customers.show', ['company' => $scenario['company']->id, 'id' => $customer->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('store/customers/show')
            ->where('store_customer.id', $customer->id)
            ->has('store_orders', 0));
});

test('la tarjeta del cliente consulta su comprador vinculado', function () {
    $scenario = storeOrderScenario();

    $client = Client::factory()->create(['company_id' => $scenario['company']->id]);
    $customer = StoreCustomer::factory()->linkedTo($client->id)->create(['company_id' => $scenario['company']->id]);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->getJson(route('store-customers.by-client', ['company' => $scenario['company']->id, 'client' => $client->id]))
        ->assertOk()
        ->assertJsonPath('data.id', $customer->id);

    $unlinked = Client::factory()->create(['company_id' => $scenario['company']->id]);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->getJson(route('store-customers.by-client', ['company' => $scenario['company']->id, 'client' => $unlinked->id]))
        ->assertOk()
        ->assertJsonPath('data', null);
});
