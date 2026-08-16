<?php

declare(strict_types=1);

use App\Modules\Category\Models\Category;

use function Pest\Laravel\actingAs;

test('a category status can be changed', function () {
    [$user, $company] = createUserWithCompany();

    $category = Category::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('categories.update-status', ['company' => $company->id, 'id' => $category->id]), [
            'status' => 'inactive',
        ]);

    $response->assertRedirect(route('categories.index', ['company' => $company->id]));
    $response->assertSessionHas('success');
    expect($category->fresh()->status)->toBe('inactive');
});

test('an inactive category can be reactivated', function () {
    [$user, $company] = createUserWithCompany();

    $category = Category::factory()->inactive()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('categories.update-status', ['company' => $company->id, 'id' => $category->id]), [
            'status' => 'active',
        ]);

    expect($category->fresh()->status)->toBe('active');
});

test('the status must be a valid value', function () {
    [$user, $company] = createUserWithCompany();

    $category = Category::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('categories.update-status', ['company' => $company->id, 'id' => $category->id]), [
            'status' => 'deleted',
        ]);

    $response->assertSessionHasErrors('status');
    expect($category->fresh()->status)->toBe('active');
});

test('a user without permission cannot change category status', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['categories.list']);

    $category = Category::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('categories.update-status', ['company' => $company->id, 'id' => $category->id]), [
            'status' => 'inactive',
        ]);

    $response->assertForbidden();
});
