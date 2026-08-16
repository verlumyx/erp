<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;

use function Pest\Laravel\actingAs;

test('an item can be deactivated', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('items.update-status', ['company' => $company->id, 'id' => $item->id]), [
            'status' => 'inactive',
        ]);

    $response->assertRedirect(route('items.index', ['company' => $company->id]));
    expect($item->refresh()->status)->toBe('inactive');
});

test('an item can be reactivated', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->inactive()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('items.update-status', ['company' => $company->id, 'id' => $item->id]), [
            'status' => 'active',
        ]);

    expect($item->refresh()->status)->toBe('active');
});

test('the status must be a valid value', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('items.update-status', ['company' => $company->id, 'id' => $item->id]), [
            'status' => 'deleted',
        ])
        ->assertSessionHasErrors('status');
});

test('an item from another company cannot change status', function () {
    [$user, $company] = createUserWithCompany();

    $foreignItem = Item::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('items.update-status', ['company' => $company->id, 'id' => $foreignItem->id]), [
            'status' => 'inactive',
        ])
        ->assertNotFound();
});

test('a user without permission cannot change the status', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['items.list']);

    $item = Item::factory()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('items.update-status', ['company' => $company->id, 'id' => $item->id]), [
            'status' => 'inactive',
        ])
        ->assertForbidden();
});
