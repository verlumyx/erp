<?php

declare(strict_types=1);

use App\Modules\Category\Models\Category;

use function Pest\Laravel\actingAs;

test('the categories index renders with categories', function () {
    [$user, $company] = createUserWithCompany();

    Category::factory()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('categories.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('categories/index')
        ->has('categories', 3)
        ->where('meta.total', 3)
    );
});

test('categories are sorted by their order column', function () {
    [$user, $company] = createUserWithCompany();

    Category::factory()->create(['company_id' => $company->id, 'name' => 'Tercera', 'order' => 30]);
    Category::factory()->create(['company_id' => $company->id, 'name' => 'Primera', 'order' => 10]);
    Category::factory()->create(['company_id' => $company->id, 'name' => 'Segunda', 'order' => 20]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('categories.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page
        ->where('categories.0.name', 'Primera')
        ->where('categories.1.name', 'Segunda')
        ->where('categories.2.name', 'Tercera')
    );
});

test('categories can be filtered by name', function () {
    [$user, $company] = createUserWithCompany();

    Category::factory()->create(['company_id' => $company->id, 'name' => 'Bebidas frías']);
    Category::factory()->create(['company_id' => $company->id, 'name' => 'Limpieza']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('categories.index', ['company' => $company->id, 'name' => 'Bebidas']));

    $response->assertInertia(fn ($page) => $page
        ->has('categories', 1)
        ->where('categories.0.name', 'Bebidas frías')
    );
});

test('categories can be filtered by description', function () {
    [$user, $company] = createUserWithCompany();

    $target = Category::factory()->create([
        'company_id' => $company->id,
        'name' => 'Alfa',
        'description' => 'Productos importados',
    ]);
    Category::factory()->create([
        'company_id' => $company->id,
        'name' => 'Beta',
        'description' => 'Productos nacionales',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('categories.index', ['company' => $company->id, 'description' => 'importados']));

    $response->assertInertia(fn ($page) => $page
        ->has('categories', 1)
        ->where('categories.0.id', $target->id)
    );
});

test('categories can be filtered by code', function () {
    [$user, $company] = createUserWithCompany();

    $target = Category::factory()->create(['company_id' => $company->id, 'code' => 'CAT000042']);
    Category::factory()->create(['company_id' => $company->id, 'code' => 'CAT000099']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('categories.index', ['company' => $company->id, 'code' => 'CAT000042']));

    $response->assertInertia(fn ($page) => $page
        ->has('categories', 1)
        ->where('categories.0.id', $target->id)
    );
});

test('categories can be filtered by status', function () {
    [$user, $company] = createUserWithCompany();

    Category::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    Category::factory()->inactive()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('categories.index', ['company' => $company->id, 'status' => 'inactive']));

    $response->assertInertia(fn ($page) => $page->has('categories', 1));
});

test('category field filters combine with AND', function () {
    [$user, $company] = createUserWithCompany();

    $target = Category::factory()->create([
        'company_id' => $company->id,
        'name' => 'Bebidas frías',
        'description' => 'importadas',
    ]);
    Category::factory()->create([
        'company_id' => $company->id,
        'name' => 'Bebidas calientes',
        'description' => 'nacionales',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('categories.index', [
            'company' => $company->id,
            'name' => 'Bebidas',
            'description' => 'importadas',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->has('categories', 1)
        ->where('categories.0.id', $target->id)
    );
});

test('the index only shows categories from the active company', function () {
    [$user, $company] = createUserWithCompany();

    Category::factory()->count(2)->create(['company_id' => $company->id]);
    Category::factory()->count(3)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('categories.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page
        ->has('categories', 2)
        ->where('meta.total', 2)
    );
});

test('a user without permission cannot list categories', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('categories.index', ['company' => $company->id]));

    $response->assertForbidden();
});
