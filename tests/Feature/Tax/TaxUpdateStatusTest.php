<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Tax\Models\Tax;

use function Pest\Laravel\actingAs;

test('a tax status can be changed', function () {
    [$user, $company] = createUserWithCompany();

    $tax = Tax::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('taxes.update-status', ['company' => $company->id, 'id' => $tax->id]), [
            'status' => 'inactive',
        ]);

    $response->assertRedirect(route('taxes.index', ['company' => $company->id]));
    $response->assertSessionHas('success');
    expect($tax->fresh()->status)->toBe('inactive');
});

test('the status must be a valid value', function () {
    [$user, $company] = createUserWithCompany();

    $tax = Tax::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('taxes.update-status', ['company' => $company->id, 'id' => $tax->id]), [
            'status' => 'deleted',
        ]);

    $response->assertSessionHasErrors('status');
    expect($tax->fresh()->status)->toBe('active');
});

test('a tax assigned as the sale tax of an active item cannot be deactivated', function () {
    [$user, $company] = createUserWithCompany();

    $tax = Tax::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    Item::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
        'sale_tax_id' => $tax->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('taxes.update-status', ['company' => $company->id, 'id' => $tax->id]), [
            'status' => 'inactive',
        ]);

    $response->assertSessionHasErrors('status');
    expect($tax->fresh()->status)->toBe('active');
});

test('a tax assigned as the purchase tax of an active item cannot be deactivated', function () {
    [$user, $company] = createUserWithCompany();

    $tax = Tax::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    Item::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
        'purchase_tax_id' => $tax->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('taxes.update-status', ['company' => $company->id, 'id' => $tax->id]), [
            'status' => 'inactive',
        ]);

    $response->assertSessionHasErrors('status');
    expect($tax->fresh()->status)->toBe('active');
});

test('a tax assigned only to inactive items can be deactivated', function () {
    [$user, $company] = createUserWithCompany();

    $tax = Tax::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    Item::factory()->create([
        'company_id' => $company->id,
        'status' => 'inactive',
        'sale_tax_id' => $tax->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('taxes.update-status', ['company' => $company->id, 'id' => $tax->id]), [
            'status' => 'inactive',
        ]);

    $response->assertSessionHasNoErrors();
    expect($tax->fresh()->status)->toBe('inactive');
});

test('an in-use tax can still be reactivated', function () {
    [$user, $company] = createUserWithCompany();

    $tax = Tax::factory()->inactive()->create(['company_id' => $company->id]);
    Item::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
        'sale_tax_id' => $tax->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('taxes.update-status', ['company' => $company->id, 'id' => $tax->id]), [
            'status' => 'active',
        ]);

    $response->assertSessionHasNoErrors();
    expect($tax->fresh()->status)->toBe('active');
});

test('a user without permission cannot change the tax status', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['taxes.list']);

    $tax = Tax::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('taxes.update-status', ['company' => $company->id, 'id' => $tax->id]), [
            'status' => 'inactive',
        ]);

    $response->assertForbidden();
});
