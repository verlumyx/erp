<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\ClientType\Models\ClientType;
use App\Modules\PriceList\Models\PriceList;

use function Pest\Laravel\actingAs;

test('a client can be created', function () {
    [$user, $company] = createUserWithCompany();

    $payload = clientPayload([
        'legal_name' => 'Camila Rojas Distribuciones C.A.',
        'email' => 'contact@acme.test',
        'phone' => '+58 412 555 1234',
        'payment_term_days' => 30,
        'credit_limit' => 5000,
        'discount_percent' => 5,
        'notes' => 'Important client',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('clients.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $client = Client::find($payload['id']);
    expect($client)->not->toBeNull();
    expect($client->name)->toBe('Camila Rojas');
    expect($client->document_type)->toBe('V');
    expect($client->document_number)->toBe('12345678');
    expect($client->payment_term_days)->toBe(30);
    expect((float) $client->credit_limit)->toBe(5000.0);
    expect((float) $client->discount_percent)->toBe(5.0);
    expect($client->credit_blocked)->toBe('no');
    expect($client->status)->toBe('active');
    expect($client->created_by)->toBe($user->id);
    expect($client->company_id)->toBe($company->id);
    expect($client->code)->toBe('CLI000001');
});

test('the balances always start at zero and cannot be sent from the client', function () {
    [$user, $company] = createUserWithCompany();

    $payload = clientPayload([
        'current_balance' => 999,
        'advance_balance' => 500,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), $payload);

    $client = Client::find($payload['id']);
    expect((float) $client->current_balance)->toBe(0.0);
    expect((float) $client->advance_balance)->toBe(0.0);
});

test('the code auto-increments per company', function () {
    [$user, $company] = createUserWithCompany();

    $first = clientPayload(['document_number' => '11111111']);
    $second = clientPayload(['document_number' => '22222222', 'name' => 'Second']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), $first);
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), $second);

    expect(Client::find($first['id'])->code)->toBe('CLI000001');
    expect(Client::find($second['id'])->code)->toBe('CLI000002');
});

test('each company has its own code sequence', function () {
    [$userA, $companyA] = createUserWithCompany();
    [$userB, $companyB] = createUserWithCompany();

    $payloadA = clientPayload(['name' => 'Client A']);
    $payloadB = clientPayload(['name' => 'Client B']);

    actingAs($userA)->withSession(['current_company_id' => $companyA->id])
        ->post(route('clients.store', ['company' => $companyA->id]), $payloadA);
    actingAs($userB)->withSession(['current_company_id' => $companyB->id])
        ->post(route('clients.store', ['company' => $companyB->id]), $payloadB);

    expect(Client::find($payloadA['id'])->code)->toBe('CLI000001');
    expect(Client::find($payloadB['id'])->code)->toBe('CLI000001');
});

test('a client can be created with its contacts and addresses', function () {
    [$user, $company] = createUserWithCompany();

    $clientType = ClientType::factory()->create(['company_id' => $company->id]);
    $priceList = PriceList::factory()->create(['company_id' => $company->id]);

    $payload = clientPayload([
        'client_type_id' => $clientType->id,
        'price_list_id' => $priceList->id,
        'salesperson_id' => $user->id,
        'contacts' => [
            [
                'name' => 'María Pérez',
                'position' => 'Compras',
                'email' => 'maria@acme.test',
                'phone' => '+58 412 000 0000',
                'is_primary' => 'yes',
            ],
            ['name' => 'Luis Rojas', 'is_primary' => 'no'],
        ],
        'addresses' => [
            [
                'type' => 'billing',
                'name' => 'Sede fiscal',
                'address' => 'Av. Principal, Torre A',
                'city' => 'Caracas',
                'is_default' => 'yes',
            ],
            [
                'type' => 'shipping',
                'name' => 'Sucursal Centro',
                'address' => 'Calle 5, Local 3',
                'latitude' => '10.4806',
                'longitude' => '-66.9036',
                'is_default' => 'yes',
            ],
        ],
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), $payload);

    $response->assertSessionHasNoErrors();

    $client = Client::with(['contacts', 'addresses'])->find($payload['id']);
    expect($client->client_type_id)->toBe($clientType->id);
    expect($client->price_list_id)->toBe($priceList->id);
    expect($client->salesperson_id)->toBe($user->id);
    expect($client->contacts)->toHaveCount(2);
    expect($client->addresses)->toHaveCount(2);

    $primary = $client->contacts->firstWhere('is_primary', 'yes');
    expect($primary->name)->toBe('María Pérez');
    expect($primary->company_id)->toBe($company->id);

    $shipping = $client->addresses->firstWhere('type', 'shipping');
    expect($shipping->name)->toBe('Sucursal Centro');
    expect((float) $shipping->latitude)->toBe(10.4806);
    expect($shipping->is_default)->toBe('yes');
    expect($shipping->company_id)->toBe($company->id);
});

test('the name is required', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), clientPayload(['name' => '']))
        ->assertSessionHasErrors('name');
});

test('the document number is required and must be digits only', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), clientPayload(['document_number' => '']))
        ->assertSessionHasErrors('document_number');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), clientPayload(['document_number' => 'V-1234-5']))
        ->assertSessionHasErrors('document_number');
});

test('the rif must be unique within the company', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->create([
        'company_id' => $company->id,
        'document_type' => 'V',
        'document_number' => '12345678',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), clientPayload())
        ->assertSessionHasErrors('document_number');
});

test('the same number with a different rif letter is accepted', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->create([
        'company_id' => $company->id,
        'document_type' => 'V',
        'document_number' => '12345678',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), clientPayload([
            'document_type' => 'J',
        ]))
        ->assertSessionHasNoErrors();
});

test('another company can reuse the same rif', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->create([
        'document_type' => 'V',
        'document_number' => '12345678',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), clientPayload())
        ->assertSessionHasNoErrors();
});

test('the email must be unique within the company', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->create(['company_id' => $company->id, 'email' => 'taken@acme.test']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), clientPayload([
            'email' => 'taken@acme.test',
        ]))
        ->assertSessionHasErrors('email');
});

test('the email must be a valid address', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), clientPayload([
            'email' => 'not-an-email',
        ]))
        ->assertSessionHasErrors('email');
});

test('only one contact can be the primary one', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), clientPayload([
            'contacts' => [
                ['name' => 'María Pérez', 'is_primary' => 'yes'],
                ['name' => 'Luis Rojas', 'is_primary' => 'yes'],
            ],
        ]))
        ->assertSessionHasErrors('contacts');
});

test('only one address per type can be the default one', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), clientPayload([
            'addresses' => [
                ['type' => 'shipping', 'name' => 'Sucursal 1', 'address' => 'Av. Principal', 'is_default' => 'yes'],
                ['type' => 'shipping', 'name' => 'Sucursal 2', 'address' => 'Av. Secundaria', 'is_default' => 'yes'],
            ],
        ]))
        ->assertSessionHasErrors('addresses.1.is_default');
});

test('a client type from another company is rejected', function () {
    [$user, $company] = createUserWithCompany();

    $foreignType = ClientType::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), clientPayload([
            'client_type_id' => $foreignType->id,
        ]))
        ->assertSessionHasErrors('client_type_id');
});

test('a price list from another company is rejected', function () {
    [$user, $company] = createUserWithCompany();

    $foreignPriceList = PriceList::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), clientPayload([
            'price_list_id' => $foreignPriceList->id,
        ]))
        ->assertSessionHasErrors('price_list_id');
});

test('a user without permission cannot create a client', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['clients.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), clientPayload())
        ->assertForbidden();
});
