<?php

declare(strict_types=1);

use App\Modules\Account\Models\Account;
use App\Modules\Account\Models\Profile;
use App\Modules\Service\Models\Service;

use function Pest\Laravel\actingAs;

// El frontend de este módulo aún no está implementado (ver README): no validamos
// la existencia del componente Inertia, solo la respuesta y los props del backend.
beforeEach(fn () => config(['inertia.testing.ensure_pages_exist' => false]));

test('the show page renders with profiles and a computed resumen', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id, 'max_profiles' => 4]);
    $account = Account::factory()->forService($service)->create();

    Profile::factory()->create(['account_id' => $account->id, 'number' => 1, 'status' => 'available']);
    Profile::factory()->create(['account_id' => $account->id, 'number' => 2, 'status' => 'occupied']);
    Profile::factory()->create(['account_id' => $account->id, 'number' => 3, 'status' => 'occupied']);
    Profile::factory()->create(['account_id' => $account->id, 'number' => 4, 'status' => 'maintenance']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('accounts.show', ['company' => $company->id, 'id' => $account->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('accounts/show')
        ->where('account.id', $account->id)
        ->where('account.profiles_summary.total', 4)
        ->where('account.profiles_summary.available', 1)
        ->where('account.profiles_summary.occupied', 2)
        ->where('account.profiles_summary.maintenance', 1)
        ->count('account.profiles', 4)
    );
});

test('the show response never exposes the encrypted password', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);
    $account = Account::factory()->forService($service)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('accounts.show', ['company' => $company->id, 'id' => $account->id]));

    $response->assertInertia(fn ($page) => $page
        ->component('accounts/show')
        ->missing('account.password_encrypted')
        ->missing('account.password')
    );
});

test('a account from another company cannot be viewed', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();

    $service = Service::factory()->create(['company_id' => $otherCompany->id]);
    $account = Account::factory()->forService($service)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('accounts.show', ['company' => $company->id, 'id' => $account->id]));

    $response->assertNotFound();
});
