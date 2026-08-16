<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\Client\Models\ClientContact;

use function Pest\Laravel\actingAs;

test('a client can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create([
        'company_id' => $company->id,
        'name' => 'Old Name',
        'payment_term_days' => 0,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('clients.update', ['company' => $company->id, 'id' => $client->id]),
            clientPayload([
                'name' => 'New Name',
                'payment_term_days' => 45,
                'email' => 'updated@acme.test',
                'notes' => 'Updated notes',
            ])
        );

    $response->assertRedirect(route('clients.show', ['company' => $company->id, 'id' => $client->id]));
    $response->assertSessionHasNoErrors();

    $client->refresh();
    expect($client->name)->toBe('New Name');
    expect($client->email)->toBe('updated@acme.test');
    expect($client->payment_term_days)->toBe(45);
});

test('updating never touches the derived balances', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->withBalance(250)->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('clients.update', ['company' => $company->id, 'id' => $client->id]),
            clientPayload(['current_balance' => 0, 'advance_balance' => 900])
        );

    $client->refresh();
    expect((float) $client->current_balance)->toBe(250.0);
    expect((float) $client->advance_balance)->toBe(0.0);
});

test('an existing contact is updated instead of duplicated', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create(['company_id' => $company->id]);
    $contact = ClientContact::factory()->create([
        'client_id' => $client->id,
        'company_id' => $company->id,
        'name' => 'Old Name',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('clients.update', ['company' => $company->id, 'id' => $client->id]),
            clientPayload([
                'contacts' => [
                    ['id' => $contact->id, 'name' => 'New Name', 'is_primary' => 'yes'],
                ],
            ])
        )
        ->assertSessionHasNoErrors();

    expect(ClientContact::where('client_id', $client->id)->count())->toBe(1);
    expect($contact->fresh()->name)->toBe('New Name');
    expect($contact->fresh()->is_primary)->toBe('yes');
});

test('a contact that is no longer sent is deactivated, not deleted', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create(['company_id' => $company->id]);
    $contact = ClientContact::factory()->create([
        'client_id' => $client->id,
        'company_id' => $company->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('clients.update', ['company' => $company->id, 'id' => $client->id]),
            clientPayload(['contacts' => []])
        )
        ->assertSessionHasNoErrors();

    expect(ClientContact::find($contact->id))->not->toBeNull();
    expect($contact->fresh()->status)->toBe('inactive');
});

test('an address that is no longer sent is deactivated, not deleted', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create(['company_id' => $company->id]);
    $address = ClientAddress::factory()->create([
        'client_id' => $client->id,
        'company_id' => $company->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('clients.update', ['company' => $company->id, 'id' => $client->id]),
            clientPayload([
                'addresses' => [
                    [
                        'type' => 'shipping',
                        'name' => 'Sucursal Centro',
                        'address' => 'Calle 5, Local 3',
                        'is_default' => 'no',
                    ],
                ],
            ])
        )
        ->assertSessionHasNoErrors();

    expect($address->fresh()->status)->toBe('inactive');
    expect(ClientAddress::where('client_id', $client->id)->count())->toBe(2);
});

test('a contact id belonging to another client is added as a new row', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create(['company_id' => $company->id]);
    $other = Client::factory()->create(['company_id' => $company->id]);
    $foreignContact = ClientContact::factory()->create([
        'client_id' => $other->id,
        'company_id' => $company->id,
        'name' => 'Contacto ajeno',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('clients.update', ['company' => $company->id, 'id' => $client->id]),
            clientPayload([
                'contacts' => [
                    ['id' => $foreignContact->id, 'name' => 'Secuestrado', 'is_primary' => 'no'],
                ],
            ])
        )
        ->assertSessionHasNoErrors();

    expect($foreignContact->fresh()->name)->toBe('Contacto ajeno');
    expect($foreignContact->fresh()->client_id)->toBe($other->id);
    expect(ClientContact::where('client_id', $client->id)->count())->toBe(1);
});

test('a client keeps its own email when updating', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create(['company_id' => $company->id, 'email' => 'mine@acme.test']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('clients.update', ['company' => $company->id, 'id' => $client->id]),
            clientPayload(['email' => 'mine@acme.test'])
        )
        ->assertSessionHasNoErrors();
});

test('a client cannot take another clients email', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->create(['company_id' => $company->id, 'email' => 'someone@acme.test']);
    $client = Client::factory()->create(['company_id' => $company->id, 'email' => 'mine@acme.test']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('clients.update', ['company' => $company->id, 'id' => $client->id]),
            clientPayload(['email' => 'someone@acme.test'])
        )
        ->assertSessionHasErrors('email');
});

test('the rif of another client in the same company is rejected', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->create([
        'company_id' => $company->id,
        'document_type' => 'V',
        'document_number' => '12345678',
    ]);
    $client = Client::factory()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('clients.update', ['company' => $company->id, 'id' => $client->id]),
            clientPayload()
        )
        ->assertSessionHasErrors('document_number');
});

test('a client keeps its own rif when updated', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create([
        'company_id' => $company->id,
        'document_type' => 'V',
        'document_number' => '12345678',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('clients.update', ['company' => $company->id, 'id' => $client->id]),
            clientPayload(['email' => $client->email])
        )
        ->assertSessionHasNoErrors();
});

test('a client of another company cannot be updated', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = Client::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('clients.update', ['company' => $company->id, 'id' => $foreign->id]),
            clientPayload()
        )
        ->assertNotFound();
});

test('a user without permission cannot update a client', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['clients.list']);

    $client = Client::factory()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('clients.update', ['company' => $company->id, 'id' => $client->id]),
            clientPayload()
        )
        ->assertForbidden();
});
