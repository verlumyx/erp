<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a client can be created', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Acme Corp',
            'phone' => '+58 412 555 1234',
            'email' => 'contact@acme.test',
            'notes' => 'Important client',
        ]);

    $response->assertRedirect(route('clients.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $client = Client::find($id);
    expect($client)->not->toBeNull();
    expect($client->name)->toBe('Acme Corp');
    expect($client->status)->toBe('active');
    expect($client->created_by)->toBe($user->id);
    expect($client->company_id)->toBe($company->id);
    expect($client->code)->toBe('CLI000001');
});

test('the code auto-increments per company', function () {
    [$user, $company] = createUserWithCompany();

    $first = (string) Str::uuid7();
    $second = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), [
            'id' => $first,
            'name' => 'First',
        ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), [
            'id' => $second,
            'name' => 'Second',
        ]);

    expect(Client::find($first)->code)->toBe('CLI000001');
    expect(Client::find($second)->code)->toBe('CLI000002');
});

test('each company has its own code sequence', function () {
    [$userA, $companyA] = createUserWithCompany();
    [$userB, $companyB] = createUserWithCompany();

    $idA = (string) Str::uuid7();
    $idB = (string) Str::uuid7();

    actingAs($userA)
        ->withSession(['current_company_id' => $companyA->id])
        ->post(route('clients.store', ['company' => $companyA->id]), [
            'id' => $idA,
            'name' => 'Client A',
        ]);

    actingAs($userB)
        ->withSession(['current_company_id' => $companyB->id])
        ->post(route('clients.store', ['company' => $companyB->id]), [
            'id' => $idB,
            'name' => 'Client B',
        ]);

    expect(Client::find($idA)->code)->toBe('CLI000001');
    expect(Client::find($idB)->code)->toBe('CLI000001');
});

test('a client can be created without optional fields', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Minimal Client',
        ]);

    $response->assertSessionHasNoErrors();
    expect(Client::find($id)?->name)->toBe('Minimal Client');
});

test('the name is required', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => '',
        ]);

    $response->assertSessionHasErrors('name');
});

test('the email must be unique', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->create(['company_id' => $company->id, 'email' => 'taken@acme.test']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Duplicate Email',
            'email' => 'taken@acme.test',
        ]);

    $response->assertSessionHasErrors('email');
});

test('the email must be a valid address', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Bad Email',
            'email' => 'not-an-email',
        ]);

    $response->assertSessionHasErrors('email');
});

test('a user without permission cannot create a client', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['clients.list']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('clients.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Forbidden',
        ]);

    $response->assertForbidden();
});
