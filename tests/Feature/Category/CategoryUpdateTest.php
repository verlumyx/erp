<?php

declare(strict_types=1);

use App\Modules\Category\Models\Category;

use function Pest\Laravel\actingAs;

test('a category can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $category = Category::factory()->create([
        'company_id' => $company->id,
        'name' => 'Nombre viejo',
        'order' => 1,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('categories.update', ['company' => $company->id, 'id' => $category->id]), [
            'name' => 'Nombre nuevo',
            'description' => 'Descripción actualizada',
            'order' => 5,
        ]);

    $response->assertRedirect(route('categories.show', ['company' => $company->id, 'id' => $category->id]));
    $response->assertSessionHasNoErrors();

    $category->refresh();
    expect($category->name)->toBe('Nombre nuevo');
    expect($category->description)->toBe('Descripción actualizada');
    expect($category->order)->toBe(5);
});

test('a category keeps its own name when updating', function () {
    [$user, $company] = createUserWithCompany();

    $category = Category::factory()->create(['company_id' => $company->id, 'name' => 'Bebidas']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('categories.update', ['company' => $company->id, 'id' => $category->id]), [
            'name' => 'Bebidas',
        ]);

    $response->assertSessionHasNoErrors();
});

test('a category cannot take another categorys name', function () {
    [$user, $company] = createUserWithCompany();

    Category::factory()->create(['company_id' => $company->id, 'name' => 'Ocupada']);
    $category = Category::factory()->create(['company_id' => $company->id, 'name' => 'Propia']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('categories.update', ['company' => $company->id, 'id' => $category->id]), [
            'name' => 'Ocupada',
        ]);

    $response->assertSessionHasErrors('name');
});

test('a user without permission cannot update a category', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['categories.list']);

    $category = Category::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('categories.update', ['company' => $company->id, 'id' => $category->id]), [
            'name' => 'Prohibida',
        ]);

    $response->assertForbidden();
});
