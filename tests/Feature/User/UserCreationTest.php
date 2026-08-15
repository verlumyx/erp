<?php

declare(strict_types=1);

use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

function userPayload(array $overrides = []): array
{
    return array_merge([
        'id' => (string) Str::uuid(),
        'name' => 'John Doe',
        'email' => 'john_'.Str::random(6).'@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], $overrides);
}

test('a user is created when the password confirmation matches', function () {
    [$user, $company] = createUserWithCompany();
    $payload = userPayload();

    $response = actingAs($user)->post(route('users.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('users.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('users', ['email' => $payload['email']]);
});

test('creation fails when the password confirmation does not match', function () {
    [$user, $company] = createUserWithCompany();
    $payload = userPayload(['password_confirmation' => 'different-password']);

    $response = actingAs($user)->post(route('users.store', ['company' => $company->id]), $payload);

    $response->assertSessionHasErrors('password');
    $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
});
