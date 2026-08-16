<?php

declare(strict_types=1);

use App\Modules\ClientType\Models\ClientType;

use function Pest\Laravel\actingAs;

test('a client type can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $clientType = ClientType::factory()->create([
        'company_id' => $company->id,
        'name' => 'Mayor',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('client-types.update', ['company' => $company->id, 'id' => $clientType->id]), [
            'name' => 'Mayorista',
            'description' => 'Clientes de compra por volumen',
        ]);

    $response->assertRedirect(route('client-types.show', ['company' => $company->id, 'id' => $clientType->id]));
    $response->assertSessionHasNoErrors();

    $clientType->refresh();
    expect($clientType->name)->toBe('Mayorista');
    expect($clientType->description)->toBe('Clientes de compra por volumen');
});

test('updating keeps its own name valid', function () {
    [$user, $company] = createUserWithCompany();

    $clientType = ClientType::factory()->create([
        'company_id' => $company->id,
        'name' => 'Detalle',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('client-types.update', ['company' => $company->id, 'id' => $clientType->id]), [
            'name' => 'Detalle',
            'description' => 'Actualizado',
        ]);

    $response->assertSessionHasNoErrors();
    expect($clientType->fresh()->description)->toBe('Actualizado');
});

test('the updated name must not collide with another client type', function () {
    [$user, $company] = createUserWithCompany();

    ClientType::factory()->create(['company_id' => $company->id, 'name' => 'Mayorista']);
    $clientType = ClientType::factory()->create(['company_id' => $company->id, 'name' => 'Detalle']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('client-types.update', ['company' => $company->id, 'id' => $clientType->id]), [
            'name' => 'Mayorista',
        ]);

    $response->assertSessionHasErrors('name');
    expect($clientType->fresh()->name)->toBe('Detalle');
});

test('a user without permission cannot update a client type', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['client-types.list']);

    $clientType = ClientType::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('client-types.update', ['company' => $company->id, 'id' => $clientType->id]), [
            'name' => 'Forbidden',
        ]);

    $response->assertForbidden();
});
