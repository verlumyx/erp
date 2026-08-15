<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

test('the company dashboard renders for a member of the company', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('company.dashboard', ['company' => $company->id]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('dashboard'));
});

test('the company dashboard requires authentication', function () {
    $response = $this->get('/00000000-0000-0000-0000-000000000000/dashboard');

    $response->assertRedirect(route('login'));
});
