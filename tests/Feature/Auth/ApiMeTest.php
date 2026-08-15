<?php

declare(strict_types=1);

test('an authenticated user can fetch their profile', function () {
    [$user, $company] = createUserWithCompany();
    $token = $user->createToken('mobile')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/me');

    $response->assertSuccessful()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonPath('user.companies.0.id', $company->id);
});

test('the me endpoint requires authentication', function () {
    $this->getJson('/api/me')->assertUnauthorized();
});
