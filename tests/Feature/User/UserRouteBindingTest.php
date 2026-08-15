<?php

declare(strict_types=1);

use App\Modules\User\Models\User;

use function Pest\Laravel\actingAs;

test('users.edit binds the user id and not the company id', function () {
    [$user, $company] = createUserWithCompany();
    $target = User::factory()->create();

    expect($company->id)->not->toBe($target->id);

    $response = actingAs($user)->get(route('users.edit', [
        'company' => $company->id,
        'id' => $target->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/edit')
        ->where('user.id', $target->id)
    );
});

test('users.show binds the user id and not the company id', function () {
    [$user, $company] = createUserWithCompany();
    $target = User::factory()->create();

    $response = actingAs($user)->get(route('users.show', [
        'company' => $company->id,
        'id' => $target->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/show')
        ->where('user.id', $target->id)
    );
});
