<?php

declare(strict_types=1);

test('an authenticated user can log out and the token is revoked', function () {
    [$user] = createUserWithCompany();
    $token = $user->createToken('mobile')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/logout');

    $response->assertSuccessful();
    expect($user->tokens()->count())->toBe(0);
});

test('logout only revokes the current device token', function () {
    [$user] = createUserWithCompany();
    $currentToken = $user->createToken('device-1')->plainTextToken;
    $user->createToken('device-2');

    $this->withHeader('Authorization', 'Bearer '.$currentToken)
        ->postJson('/api/logout')
        ->assertSuccessful();

    expect($user->tokens()->count())->toBe(1);
    expect($user->tokens()->first()->name)->toBe('device-2');
});

test('the logout endpoint requires authentication', function () {
    $this->postJson('/api/logout')->assertUnauthorized();
});
