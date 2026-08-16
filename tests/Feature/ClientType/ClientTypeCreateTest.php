<?php

declare(strict_types=1);

use App\Modules\ClientType\Models\ClientType;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a client type can be created', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('client-types.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Mayorista',
            'description' => 'Clientes de compra por volumen',
        ]);

    $response->assertRedirect(route('client-types.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $clientType = ClientType::find($id);
    expect($clientType)->not->toBeNull();
    expect($clientType->name)->toBe('Mayorista');
    expect($clientType->status)->toBe('active');
    expect($clientType->created_by)->toBe($user->id);
    expect($clientType->company_id)->toBe($company->id);
    expect($clientType->code)->toBe('TCL000001');
});

test('the code auto-increments per company', function () {
    [$user, $company] = createUserWithCompany();

    $first = (string) Str::uuid7();
    $second = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('client-types.store', ['company' => $company->id]), [
            'id' => $first,
            'name' => 'Mayorista',
        ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('client-types.store', ['company' => $company->id]), [
            'id' => $second,
            'name' => 'Detalle',
        ]);

    expect(ClientType::find($first)->code)->toBe('TCL000001');
    expect(ClientType::find($second)->code)->toBe('TCL000002');
});

test('each company has its own code sequence', function () {
    [$userA, $companyA] = createUserWithCompany();
    [$userB, $companyB] = createUserWithCompany();

    $idA = (string) Str::uuid7();
    $idB = (string) Str::uuid7();

    actingAs($userA)
        ->withSession(['current_company_id' => $companyA->id])
        ->post(route('client-types.store', ['company' => $companyA->id]), [
            'id' => $idA,
            'name' => 'Corporativo',
        ]);

    actingAs($userB)
        ->withSession(['current_company_id' => $companyB->id])
        ->post(route('client-types.store', ['company' => $companyB->id]), [
            'id' => $idB,
            'name' => 'Corporativo',
        ]);

    expect(ClientType::find($idA)->code)->toBe('TCL000001');
    expect(ClientType::find($idB)->code)->toBe('TCL000001');
});

test('a client type can be created without a description', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('client-types.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Detalle',
        ]);

    $response->assertSessionHasNoErrors();
    expect(ClientType::find($id)?->description)->toBeNull();
});

test('the name is required', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('client-types.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => '',
        ]);

    $response->assertSessionHasErrors('name');
});

test('the name must be unique per company', function () {
    [$user, $company] = createUserWithCompany();

    ClientType::factory()->create(['company_id' => $company->id, 'name' => 'Mayorista']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('client-types.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Mayorista',
        ]);

    $response->assertSessionHasErrors('name');
});

test('another company can reuse the same name', function () {
    [$user, $company] = createUserWithCompany();

    ClientType::factory()->create(['name' => 'Mayorista']);

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('client-types.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Mayorista',
        ]);

    $response->assertSessionHasNoErrors();
    expect(ClientType::find($id))->not->toBeNull();
});

test('a user without permission cannot create a client type', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['client-types.list']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('client-types.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Forbidden',
        ]);

    $response->assertForbidden();
});
