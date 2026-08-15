<?php

declare(strict_types=1);

use App\Modules\Auth\Services\LoginService;

test('a user can log in and receives a token', function () {
    [$user] = createUserWithCompany();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'iPhone 15',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email', 'companies'],
        ])
        ->assertJsonPath('user.id', $user->id);

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'name' => 'iPhone 15',
    ]);
});

test('login fails with invalid credentials', function () {
    [$user] = createUserWithCompany();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized();
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('login requires email and password', function () {
    $response = $this->postJson('/api/login', []);

    $response->assertJsonValidationErrors(['email', 'password']);
});

test('login is blocked when the device limit is reached', function () {
    [$user] = createUserWithCompany();

    $user->createToken('device-1');
    $user->createToken('device-2');

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertForbidden();
    expect($user->tokens()->count())->toBe(LoginService::MAX_DEVICES);
});
