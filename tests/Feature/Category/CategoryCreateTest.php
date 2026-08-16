<?php

declare(strict_types=1);

use App\Modules\Category\Models\Category;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a category can be created', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('categories.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Bebidas',
            'description' => 'Refrescos, jugos y aguas',
            'order' => 3,
        ]);

    $response->assertRedirect(route('categories.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $category = Category::find($id);
    expect($category)->not->toBeNull();
    expect($category->name)->toBe('Bebidas');
    expect($category->description)->toBe('Refrescos, jugos y aguas');
    expect($category->order)->toBe(3);
    expect($category->status)->toBe('active');
    expect($category->created_by)->toBe($user->id);
    expect($category->company_id)->toBe($company->id);
    expect($category->code)->toBe('CAT000001');
});

test('the code auto-increments per company', function () {
    [$user, $company] = createUserWithCompany();

    $first = (string) Str::uuid7();
    $second = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('categories.store', ['company' => $company->id]), [
            'id' => $first,
            'name' => 'Primera',
        ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('categories.store', ['company' => $company->id]), [
            'id' => $second,
            'name' => 'Segunda',
        ]);

    expect(Category::find($first)->code)->toBe('CAT000001');
    expect(Category::find($second)->code)->toBe('CAT000002');
});

test('each company has its own code sequence', function () {
    [$userA, $companyA] = createUserWithCompany();
    [$userB, $companyB] = createUserWithCompany();

    $idA = (string) Str::uuid7();
    $idB = (string) Str::uuid7();

    actingAs($userA)
        ->withSession(['current_company_id' => $companyA->id])
        ->post(route('categories.store', ['company' => $companyA->id]), [
            'id' => $idA,
            'name' => 'Categoría A',
        ]);

    actingAs($userB)
        ->withSession(['current_company_id' => $companyB->id])
        ->post(route('categories.store', ['company' => $companyB->id]), [
            'id' => $idB,
            'name' => 'Categoría B',
        ]);

    expect(Category::find($idA)->code)->toBe('CAT000001');
    expect(Category::find($idB)->code)->toBe('CAT000001');
});

test('a category can be created without optional fields', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('categories.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Mínima',
        ]);

    $response->assertSessionHasNoErrors();

    $category = Category::find($id);
    expect($category?->name)->toBe('Mínima');
    expect($category?->description)->toBeNull();
    expect($category?->order)->toBe(0);
});

test('the name is required', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('categories.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => '',
        ]);

    $response->assertSessionHasErrors('name');
});

test('the name must be unique within the company', function () {
    [$user, $company] = createUserWithCompany();

    Category::factory()->create(['company_id' => $company->id, 'name' => 'Bebidas']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('categories.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Bebidas',
        ]);

    $response->assertSessionHasErrors('name');
});

test('another company can reuse the same category name', function () {
    [$user, $company] = createUserWithCompany();

    Category::factory()->create(['name' => 'Bebidas']);

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('categories.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Bebidas',
        ]);

    $response->assertSessionHasNoErrors();
    expect(Category::find($id)?->name)->toBe('Bebidas');
});

test('the order cannot be negative', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('categories.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Orden inválido',
            'order' => -1,
        ]);

    $response->assertSessionHasErrors('order');
});

test('a user without permission cannot create a category', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['categories.list']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('categories.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Prohibida',
        ]);

    $response->assertForbidden();
});
