<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;

use function Pest\Laravel\actingAs;

test('a client status can be changed', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('clients.update-status', ['company' => $company->id, 'id' => $client->id]), [
            'status' => 'inactive',
        ]);

    $response->assertRedirect(route('clients.index', ['company' => $company->id]));
    $response->assertSessionHas('success');
    expect($client->fresh()->status)->toBe('inactive');
});

test('a client with a pending balance cannot be deactivated', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->withBalance(150)->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('clients.update-status', ['company' => $company->id, 'id' => $client->id]), [
            'status' => 'inactive',
        ])
        ->assertSessionHasErrors('status');

    expect($client->fresh()->status)->toBe('active');
});

test('a client with unapplied advances cannot be deactivated', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create([
        'company_id' => $company->id,
        'advance_balance' => 80,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('clients.update-status', ['company' => $company->id, 'id' => $client->id]), [
            'status' => 'inactive',
        ])
        ->assertSessionHasErrors('status');
});

test('a client with a balance can still be reactivated', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->withBalance(150)->inactive()->create([
        'company_id' => $company->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('clients.update-status', ['company' => $company->id, 'id' => $client->id]), [
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors();

    expect($client->fresh()->status)->toBe('active');
});

test('the status must be a valid value', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('clients.update-status', ['company' => $company->id, 'id' => $client->id]), [
            'status' => 'deleted',
        ]);

    $response->assertSessionHasErrors('status');
    expect($client->fresh()->status)->toBe('active');
});

test('a user without permission cannot change client status', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['clients.list']);

    $client = Client::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('clients.update-status', ['company' => $company->id, 'id' => $client->id]), [
            'status' => 'inactive',
        ]);

    $response->assertForbidden();
});
