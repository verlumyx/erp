<?php

declare(strict_types=1);

use App\Modules\Category\Exceptions\CategoryNotFoundException;
use App\Modules\Category\Models\Category;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('the category show page renders', function () {
    [$user, $company] = createUserWithCompany();

    $category = Category::factory()->create(['company_id' => $company->id, 'name' => 'Visible']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('categories.show', ['company' => $company->id, 'id' => $category->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('categories/show')
        ->where('category.id', $category->id)
        ->where('category.name', 'Visible')
    );
});

test('the category edit page renders', function () {
    [$user, $company] = createUserWithCompany();

    $category = Category::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('categories.edit', ['company' => $company->id, 'id' => $category->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('categories/edit')
        ->where('category.id', $category->id)
    );
});

test('showing a missing category throws a not found exception', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('categories.show', ['company' => $company->id, 'id' => (string) Str::uuid7()]));
})->throws(CategoryNotFoundException::class);

test('a category from another company is not reachable', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = Category::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('categories.show', ['company' => $company->id, 'id' => $foreign->id]));
})->throws(CategoryNotFoundException::class);
