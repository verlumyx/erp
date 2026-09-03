<?php

use App\Modules\Client\Models\Client;
use App\Modules\Store\Models\StoreCustomer;

function registerPayload(array $overrides = []): array
{
    return [
        'name' => 'Ana Pérez',
        'email' => 'ana@example.com',
        'phone' => '+58 412 555 0001',
        'password' => 'secret-1234',
        ...$overrides,
    ];
}

test('el registro crea un comprador activo y devuelve un token', function () {
    $scenario = storeOrderScenario();

    $response = $this->withHeaders(storeApiHeaders())
        ->postJson(route('api.store.customers.register'), registerPayload());

    $response->assertCreated()
        ->assertJsonPath('customer.email', 'ana@example.com')
        ->assertJsonPath('customer.is_linked', false)
        ->assertJsonStructure(['token', 'customer' => ['id', 'code', 'name']]);

    $customer = StoreCustomer::query()->where('email', 'ana@example.com')->firstOrFail();

    expect($customer->status)->toBe('active')
        ->and($customer->code)->toBe('CWE000001')
        ->and($customer->company_id)->toBe($scenario['company']->id)
        ->and($customer->password_hash)->not->toBeNull()
        ->and($customer->tokens()->count())->toBe(1);
});

test('registrarse con un RIF que ya existe en clientes vincula por rif', function () {
    $scenario = storeOrderScenario();

    $client = Client::factory()->create([
        'company_id' => $scenario['company']->id,
        'document_type' => 'V',
        'document_number' => '19876543',
    ]);

    $this->withHeaders(storeApiHeaders())
        ->postJson(route('api.store.customers.register'), registerPayload([
            'document_type' => 'V',
            'document_number' => '19876543',
        ]))
        ->assertCreated()
        ->assertJsonPath('customer.is_linked', true);

    $customer = StoreCustomer::query()->where('email', 'ana@example.com')->firstOrFail();

    expect($customer->client_id)->toBe($client->id)
        ->and($customer->link_source)->toBe('rif')
        ->and($customer->linked_at)->not->toBeNull()
        ->and($customer->linked_by)->toBeNull();
});

test('registrarse no crea un cliente en el ERP', function () {
    storeOrderScenario();

    $this->withHeaders(storeApiHeaders())
        ->postJson(route('api.store.customers.register'), registerPayload())
        ->assertCreated();

    expect(Client::query()->count())->toBe(0);
});

test('un correo repetido en la misma empresa responde 422', function () {
    $scenario = storeOrderScenario();

    StoreCustomer::factory()->create([
        'company_id' => $scenario['company']->id,
        'email' => 'ana@example.com',
    ]);

    $this->withHeaders(storeApiHeaders())
        ->postJson(route('api.store.customers.register'), registerPayload())
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('un RIF repetido entre compradores responde 422', function () {
    $scenario = storeOrderScenario();

    StoreCustomer::factory()->withDocument('V', '19876543')->create([
        'company_id' => $scenario['company']->id,
    ]);

    $this->withHeaders(storeApiHeaders())
        ->postJson(route('api.store.customers.register'), registerPayload([
            'document_type' => 'V',
            'document_number' => '19876543',
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['document_number']);
});

test('sin llave de tienda el registro responde 403', function () {
    storeOrderScenario();

    $this->postJson(route('api.store.customers.register'), registerPayload())
        ->assertForbidden();
});
