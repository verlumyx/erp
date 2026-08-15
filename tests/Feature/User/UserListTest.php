<?php

declare(strict_types=1);

use App\Modules\User\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

function attachUserToCompany(User $user, string $companyId): void
{
    $user->companies()->attach($companyId, [
        'id' => (string) Str::uuid(),
        'status' => 'active',
    ]);
}

test('users can be filtered by name', function () {
    [$actor, $company] = createUserWithCompany();

    $target = User::factory()->create(['name' => 'Findable User']);
    attachUserToCompany($target, $company->id);
    $other = User::factory()->create(['name' => 'Someone Else']);
    attachUserToCompany($other, $company->id);

    actingAs($actor)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('users.index', ['company' => $company->id, 'name' => 'Findable']))
        ->assertInertia(fn ($page) => $page
            ->has('users', 1)
            ->where('users.0.id', $target->id)
        );
});

test('users can be filtered by email', function () {
    [$actor, $company] = createUserWithCompany();

    $target = User::factory()->create(['email' => 'target@acme.test']);
    attachUserToCompany($target, $company->id);
    $other = User::factory()->create(['email' => 'other@acme.test']);
    attachUserToCompany($other, $company->id);

    actingAs($actor)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('users.index', ['company' => $company->id, 'email' => 'target@']))
        ->assertInertia(fn ($page) => $page
            ->has('users', 1)
            ->where('users.0.id', $target->id)
        );
});

test('users can be filtered by email verification', function () {
    [$actor, $company] = createUserWithCompany();

    $unverified = User::factory()->unverified()->create(['name' => 'Pending User']);
    attachUserToCompany($unverified, $company->id);
    $verified = User::factory()->create(['name' => 'Verified User']);
    attachUserToCompany($verified, $company->id);

    actingAs($actor)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('users.index', ['company' => $company->id, 'email_verified' => 'false']))
        ->assertInertia(fn ($page) => $page
            ->has('users', 1)
            ->where('users.0.id', $unverified->id)
        );
});
