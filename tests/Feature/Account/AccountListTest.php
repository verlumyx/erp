<?php

declare(strict_types=1);

use App\Modules\Account\Models\Account;
use App\Modules\Service\Models\Service;

use function Pest\Laravel\actingAs;

// El frontend de este módulo aún no está implementado (ver README): no validamos
// la existencia del componente Inertia, solo la respuesta y los props del backend.
beforeEach(fn () => config(['inertia.testing.ensure_pages_exist' => false]));

test('the index lists only accounts of the current company', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();

    $service = Service::factory()->create(['company_id' => $company->id]);
    $otherService = Service::factory()->create(['company_id' => $otherCompany->id]);

    Account::factory()->forService($service)->count(3)->create();
    Account::factory()->forService($otherService)->count(2)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('accounts.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('accounts/index')
        ->where('meta.total', 3)
        ->count('accounts', 3)
    );
});

test('the index can be filtered by status', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);

    Account::factory()->forService($service)->count(2)->create(['status' => 'active']);
    Account::factory()->forService($service)->down()->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('accounts.index', ['company' => $company->id, 'status' => 'down']));

    $response->assertInertia(fn ($page) => $page
        ->where('meta.total', 1)
        ->count('accounts', 1)
    );
});

test('a user without list permission gets a 403', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['accounts.show']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('accounts.index', ['company' => $company->id]));

    $response->assertForbidden();
});
