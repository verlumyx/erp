<?php

declare(strict_types=1);

use App\Modules\ClientType\Models\ClientType;

use function Pest\Laravel\actingAs;

test('a client type status can be changed', function () {
    [$user, $company] = createUserWithCompany();

    $clientType = ClientType::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('client-types.update-status', ['company' => $company->id, 'id' => $clientType->id]), [
            'status' => 'inactive',
        ]);

    $response->assertRedirect(route('client-types.index', ['company' => $company->id]));
    $response->assertSessionHas('success');
    expect($clientType->fresh()->status)->toBe('inactive');
});

test('the status must be a valid value', function () {
    [$user, $company] = createUserWithCompany();

    $clientType = ClientType::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('client-types.update-status', ['company' => $company->id, 'id' => $clientType->id]), [
            'status' => 'deleted',
        ]);

    $response->assertSessionHasErrors('status');
    expect($clientType->fresh()->status)->toBe('active');
});

test('a user without permission cannot change the client type status', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['client-types.list']);

    $clientType = ClientType::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('client-types.update-status', ['company' => $company->id, 'id' => $clientType->id]), [
            'status' => 'inactive',
        ]);

    $response->assertForbidden();
});
