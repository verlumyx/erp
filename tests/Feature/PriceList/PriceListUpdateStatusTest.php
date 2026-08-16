<?php

declare(strict_types=1);

use App\Modules\PriceList\Models\PriceList;

use function Pest\Laravel\actingAs;

test('a price list status can be changed', function () {
    [$user, $company] = createUserWithCompany();

    $priceList = PriceList::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('price-lists.update-status', ['company' => $company->id, 'id' => $priceList->id]), [
            'status' => 'inactive',
        ]);

    $response->assertRedirect(route('price-lists.index', ['company' => $company->id]));
    $response->assertSessionHas('success');
    expect($priceList->fresh()->status)->toBe('inactive');
});

test('an inactive price list can be reactivated', function () {
    [$user, $company] = createUserWithCompany();

    $priceList = PriceList::factory()->inactive()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('price-lists.update-status', ['company' => $company->id, 'id' => $priceList->id]), [
            'status' => 'active',
        ]);

    expect($priceList->fresh()->status)->toBe('active');
});

test('the status must be a valid value', function () {
    [$user, $company] = createUserWithCompany();

    $priceList = PriceList::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('price-lists.update-status', ['company' => $company->id, 'id' => $priceList->id]), [
            'status' => 'deleted',
        ]);

    $response->assertSessionHasErrors('status');
    expect($priceList->fresh()->status)->toBe('active');
});

test('a user without permission cannot change price list status', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['price-lists.list']);

    $priceList = PriceList::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('price-lists.update-status', ['company' => $company->id, 'id' => $priceList->id]), [
            'status' => 'inactive',
        ]);

    $response->assertForbidden();
});
