<?php

declare(strict_types=1);

use App\Modules\User\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a user without users.list cannot open the users index', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('users.index', ['company' => $company->id]))
        ->assertForbidden();
});

test('a user without users.create cannot open the create view', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['users.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('users.create', ['company' => $company->id]))
        ->assertForbidden();
});

test('a user without users.show cannot open the show view', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['users.list']);
    $target = User::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('users.show', ['company' => $company->id, 'id' => $target->id]))
        ->assertForbidden();
});

test('a user without users.update cannot open the edit view', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['users.list']);
    $target = User::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('users.edit', ['company' => $company->id, 'id' => $target->id]))
        ->assertForbidden();
});

test('a user without users.create cannot store a user', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['users.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('users.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid(),
            'name' => 'Blocked User',
            'email' => 'blocked_'.Str::random(6).'@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertForbidden();

    expect(User::where('name', 'Blocked User')->exists())->toBeFalse();
});

test('a user without users.update cannot update a user', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['users.list']);
    $target = User::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('users.update', ['company' => $company->id, 'id' => $target->id]), [
            'name' => 'Renamed',
            'email' => $target->email,
        ])
        ->assertForbidden();
});

test('a user without users.update-status cannot change a user status', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['users.list']);
    $target = User::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('users.update-status', ['company' => $company->id, 'id' => $target->id]), [
            'status' => 'inactive',
        ])
        ->assertForbidden();
});

test('granting the matching permissions allows access to the user views', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['users.list', 'users.create', 'users.show', 'users.update']);
    $target = User::factory()->create();

    $session = ['current_company_id' => $company->id];

    actingAs($user)->withSession($session)->get(route('users.index', ['company' => $company->id]))->assertOk();
    actingAs($user)->withSession($session)->get(route('users.create', ['company' => $company->id]))->assertOk();
    actingAs($user)->withSession($session)->get(route('users.show', ['company' => $company->id, 'id' => $target->id]))->assertOk();
    actingAs($user)->withSession($session)->get(route('users.edit', ['company' => $company->id, 'id' => $target->id]))->assertOk();
});
