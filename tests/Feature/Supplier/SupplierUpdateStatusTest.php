<?php

declare(strict_types=1);

use App\Modules\Supplier\Models\Supplier;

use function Pest\Laravel\actingAs;

test('a supplier can be deactivated', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update-status', ['company' => $company->id, 'id' => $supplier->id]),
            ['status' => 'inactive']
        );

    $response->assertRedirect(route('suppliers.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    expect($supplier->fresh()->status)->toBe('inactive');
});

test('a supplier can be reactivated', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->inactive()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update-status', ['company' => $company->id, 'id' => $supplier->id]),
            ['status' => 'active']
        )
        ->assertSessionHasNoErrors();

    expect($supplier->fresh()->status)->toBe('active');
});

test('a supplier with a pending balance cannot be deactivated', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->withBalance(150)->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update-status', ['company' => $company->id, 'id' => $supplier->id]),
            ['status' => 'inactive']
        )
        ->assertSessionHasErrors('status');

    expect($supplier->fresh()->status)->toBe('active');
});

test('a supplier with unapplied advances cannot be deactivated', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->create([
        'company_id' => $company->id,
        'advance_balance' => 80,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update-status', ['company' => $company->id, 'id' => $supplier->id]),
            ['status' => 'inactive']
        )
        ->assertSessionHasErrors('status');
});

test('a supplier with a balance can still be reactivated', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->withBalance(150)->inactive()->create([
        'company_id' => $company->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update-status', ['company' => $company->id, 'id' => $supplier->id]),
            ['status' => 'active']
        )
        ->assertSessionHasNoErrors();

    expect($supplier->fresh()->status)->toBe('active');
});

test('the status must be a valid value', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update-status', ['company' => $company->id, 'id' => $supplier->id]),
            ['status' => 'cancelled']
        )
        ->assertSessionHasErrors('status');
});

test('the status of a supplier of another company cannot be changed', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = Supplier::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update-status', ['company' => $company->id, 'id' => $foreign->id]),
            ['status' => 'inactive']
        )
        ->assertNotFound();
});

test('a user without permission cannot change the status', function () {
    [$user, $company] = createUserWithCompany();
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    assignRoleWithPermissions($user, $company, ['suppliers.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update-status', ['company' => $company->id, 'id' => $supplier->id]),
            ['status' => 'inactive']
        )
        ->assertForbidden();
});
