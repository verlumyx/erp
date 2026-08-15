<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;

use function Pest\Laravel\actingAs;

test('a client can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create(['company_id' => $company->id, 'name' => 'Old Name']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('clients.update', ['company' => $company->id, 'id' => $client->id]), [
            'name' => 'New Name',
            'phone' => '+58 412 000 0000',
            'email' => 'updated@acme.test',
            'notes' => 'Updated notes',
        ]);

    $response->assertRedirect(route('clients.show', ['company' => $company->id, 'id' => $client->id]));
    $response->assertSessionHasNoErrors();

    $client->refresh();
    expect($client->name)->toBe('New Name');
    expect($client->email)->toBe('updated@acme.test');
});

test('a client keeps its own email when updating', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create(['company_id' => $company->id, 'email' => 'mine@acme.test']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('clients.update', ['company' => $company->id, 'id' => $client->id]), [
            'name' => 'Same Email',
            'email' => 'mine@acme.test',
        ]);

    $response->assertSessionHasNoErrors();
});

test('a client cannot take another clients email', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->create(['company_id' => $company->id, 'email' => 'someone@acme.test']);
    $client = Client::factory()->create(['company_id' => $company->id, 'email' => 'mine@acme.test']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('clients.update', ['company' => $company->id, 'id' => $client->id]), [
            'name' => 'Conflict',
            'email' => 'someone@acme.test',
        ]);

    $response->assertSessionHasErrors('email');
});

test('a user without permission cannot update a client', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['clients.list']);

    $client = Client::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('clients.update', ['company' => $company->id, 'id' => $client->id]), [
            'name' => 'Forbidden',
        ]);

    $response->assertForbidden();
});
