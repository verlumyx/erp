<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('the users index only lists users assigned to the active company', function () {
    [$user, $company] = createUserWithCompany();

    $sameCompanyUser = User::factory()->create(['name' => 'Same Company']);
    $sameCompanyUser->companies()->attach($company->id, [
        'id' => (string) Str::uuid(),
        'status' => 'active',
    ]);

    $otherCompany = Company::create([
        'name' => 'Other Company '.uniqid(),
        'status' => 'active',
        'created_by' => $user->id,
    ]);
    $otherCompanyUser = User::factory()->create(['name' => 'Other Company User']);
    $otherCompanyUser->companies()->attach($otherCompany->id, [
        'id' => (string) Str::uuid(),
        'status' => 'active',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('users.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('users/index')
            ->where('meta.total', 2)
            ->where('users', fn ($users) => collect($users)->pluck('id')->sort()->values()->all()
                === collect([$user->id, $sameCompanyUser->id])->sort()->values()->all()
            )
        );
});
