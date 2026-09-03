<?php

use App\Modules\Store\Models\StoreCustomer;
use Database\Factories\StoreCustomerFactory;
use Laravel\Sanctum\PersonalAccessToken;

test('con credenciales válidas devuelve un token con la habilidad store-customer', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->create([
        'company_id' => $scenario['company']->id,
        'email' => 'ana@example.com',
    ]);

    $response = $this->withHeaders(storeApiHeaders())
        ->postJson(route('api.store.customers.login'), [
            'email' => 'ana@example.com',
            'password' => StoreCustomerFactory::PASSWORD,
        ]);

    $response->assertOk()->assertJsonPath('customer.id', $customer->id);

    $token = PersonalAccessToken::findToken($response->json('token'));

    expect($token)->not->toBeNull()
        ->and($token->can('store-customer'))->toBeTrue()
        ->and($customer->fresh()->last_login_at)->not->toBeNull();
});

test('una contraseña incorrecta responde 422', function () {
    $scenario = storeOrderScenario();

    StoreCustomer::factory()->create([
        'company_id' => $scenario['company']->id,
        'email' => 'ana@example.com',
    ]);

    $this->withHeaders(storeApiHeaders())
        ->postJson(route('api.store.customers.login'), [
            'email' => 'ana@example.com',
            'password' => 'otra-cosa',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('un comprador inactivo no puede iniciar sesión', function () {
    $scenario = storeOrderScenario();

    StoreCustomer::factory()->inactive()->create([
        'company_id' => $scenario['company']->id,
        'email' => 'ana@example.com',
    ]);

    $this->withHeaders(storeApiHeaders())
        ->postJson(route('api.store.customers.login'), [
            'email' => 'ana@example.com',
            'password' => StoreCustomerFactory::PASSWORD,
        ])
        ->assertForbidden();
});

test('un comprador invitado sin contraseña no puede iniciar sesión', function () {
    $scenario = storeOrderScenario();

    StoreCustomer::factory()->invited()->create([
        'company_id' => $scenario['company']->id,
        'email' => 'ana@example.com',
    ]);

    $this->withHeaders(storeApiHeaders())
        ->postJson(route('api.store.customers.login'), [
            'email' => 'ana@example.com',
            'password' => StoreCustomerFactory::PASSWORD,
        ])
        ->assertForbidden();
});
