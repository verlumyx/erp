<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;

use function Pest\Laravel\actingAs;

/**
 * The target company must differ from the prefix company: before the fix the
 * controller received the prefix company id, which exists, so the only way to
 * detect the bug is to assert a *different* company is returned.
 */
test('companies.edit binds the target company id and not the prefix company id', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);

    $target = Company::create([
        'name' => 'Target '.uniqid(),
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    expect($company->id)->not->toBe($target->id);

    $response = actingAs($user)->get(route('companies.edit', [
        'company' => $company->id,
        'id' => $target->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('companies/edit')
        ->where('company.id', $target->id)
    );
});

test('companies.show binds the target company id and not the prefix company id', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);

    $target = Company::create([
        'name' => 'Target '.uniqid(),
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $response = actingAs($user)->get(route('companies.show', [
        'company' => $company->id,
        'id' => $target->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('companies/show')
        ->where('company.id', $target->id)
    );
});
