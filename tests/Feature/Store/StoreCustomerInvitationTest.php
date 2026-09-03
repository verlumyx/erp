<?php

use App\Modules\Client\Models\Client;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Notifications\StoreCustomerInvitation;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

test('invitar desde el cliente crea un comprador invitado vinculado y envía la notificación', function () {
    Notification::fake();

    $scenario = storeOrderScenario();

    $client = Client::factory()->create([
        'company_id' => $scenario['company']->id,
        'email' => 'cliente@example.com',
    ]);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->post(route('store-customers.invite', ['company' => $scenario['company']->id, 'client' => $client->id]))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $customer = StoreCustomer::query()->where('client_id', $client->id)->firstOrFail();

    expect($customer->status)->toBe('invited')
        ->and($customer->password_hash)->toBeNull()
        ->and($customer->email)->toBe('cliente@example.com')
        ->and($customer->link_source)->toBe('invitation')
        ->and($customer->linked_by)->toBe($scenario['user']->id)
        ->and($customer->invitation_token_hash)->not->toBeNull()
        ->and($customer->invitation_expires_at)->not->toBeNull();

    Notification::assertSentTo($customer, StoreCustomerInvitation::class, function (StoreCustomerInvitation $notification): bool {
        return str_starts_with($notification->url, 'https://tienda.test/invitacion/');
    });
});

test('sin store_url configurada no se puede invitar', function () {
    Notification::fake();

    $scenario = storeOrderScenario(['store_url' => null]);

    $client = Client::factory()->create([
        'company_id' => $scenario['company']->id,
        'email' => 'cliente@example.com',
    ]);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->from(route('clients.show', ['company' => $scenario['company']->id, 'id' => $client->id]))
        ->post(route('store-customers.invite', ['company' => $scenario['company']->id, 'client' => $client->id]))
        ->assertSessionHasErrors(['store_url']);

    Notification::assertNothingSent();
});

test('un cliente ya vinculado a un comprador activo no se invita', function () {
    Notification::fake();

    $scenario = storeOrderScenario();

    $client = Client::factory()->create([
        'company_id' => $scenario['company']->id,
        'email' => 'cliente@example.com',
    ]);

    StoreCustomer::factory()->linkedTo($client->id)->create([
        'company_id' => $scenario['company']->id,
    ]);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->from('/')
        ->post(route('store-customers.invite', ['company' => $scenario['company']->id, 'client' => $client->id]))
        ->assertSessionHasErrors(['client_id']);
});

test('aceptar la invitación pone contraseña, activa la cuenta y borra el token', function () {
    $scenario = storeOrderScenario();

    $client = Client::factory()->create(['company_id' => $scenario['company']->id]);

    $customer = StoreCustomer::factory()
        ->invited('mi-token-secreto')
        ->linkedTo($client->id, 'invitation')
        ->create(['company_id' => $scenario['company']->id]);

    $this->withHeaders(storeApiHeaders())
        ->postJson(route('api.store.customers.accept-invitation', ['token' => 'mi-token-secreto']), [
            'password' => 'nueva-clave-123',
        ])
        ->assertOk()
        ->assertJsonStructure(['token', 'customer'])
        ->assertJsonPath('customer.is_linked', true);

    $customer->refresh();

    expect($customer->status)->toBe('active')
        ->and($customer->password_hash)->not->toBeNull()
        ->and($customer->email_verified_at)->not->toBeNull()
        ->and($customer->invitation_token_hash)->toBeNull()
        ->and($customer->invitation_expires_at)->toBeNull();

    $this->withHeaders(storeApiHeaders())
        ->postJson(route('api.store.customers.login'), [
            'email' => $customer->email,
            'password' => 'nueva-clave-123',
        ])
        ->assertOk();
});

test('un token vencido responde 422', function () {
    $scenario = storeOrderScenario();

    StoreCustomer::factory()
        ->invited('token-viejo')
        ->create([
            'company_id' => $scenario['company']->id,
            'invitation_expires_at' => now()->subDay(),
        ]);

    $this->withHeaders(storeApiHeaders())
        ->postJson(route('api.store.customers.accept-invitation', ['token' => 'token-viejo']), [
            'password' => 'nueva-clave-123',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
});

test('un token desconocido responde 422', function () {
    storeOrderScenario();

    $this->withHeaders(storeApiHeaders())
        ->postJson(route('api.store.customers.accept-invitation', ['token' => 'no-existe']), [
            'password' => 'nueva-clave-123',
        ])
        ->assertStatus(422);
});
