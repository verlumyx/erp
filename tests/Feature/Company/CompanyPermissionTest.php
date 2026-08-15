<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/**
 * El módulo Empresas es exclusivo del dueño del sistema (is_system_owner). El
 * acceso NO se gestiona por permisos de rol: ni siquiera un rol con acceso total
 * ("all") puede entrar si el usuario no es dueño del sistema.
 */
test('a non owner cannot open the companies index even with a full access role', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('companies.index', ['company' => $company->id]))
        ->assertForbidden();
});

test('a non owner cannot open the create view', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('companies.create', ['company' => $company->id]))
        ->assertForbidden();
});

test('a non owner cannot open the show view', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('companies.show', ['company' => $company->id, 'id' => $company->id]))
        ->assertForbidden();
});

test('a non owner cannot open the edit view', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('companies.edit', ['company' => $company->id, 'id' => $company->id]))
        ->assertForbidden();
});

test('a non owner cannot store a company', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('companies.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid(),
            'name' => 'Blocked Company',
        ])
        ->assertForbidden();

    expect(Company::where('name', 'Blocked Company')->exists())->toBeFalse();
});

test('a non owner cannot update a company', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('companies.update', ['company' => $company->id, 'id' => $company->id]), [
            'name' => 'Renamed Company',
        ])
        ->assertForbidden();
});

test('a non owner cannot update company status', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('companies.update-status', ['company' => $company->id, 'id' => $company->id]), [
            'status' => 'inactive',
        ])
        ->assertForbidden();

    expect($company->fresh()->status)->toBe('active');
});

test('a system owner can update company status', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('companies.update-status', ['company' => $company->id, 'id' => $company->id]), [
            'status' => 'inactive',
        ])
        ->assertRedirect();

    expect($company->fresh()->status)->toBe('inactive');
});

test('a system owner sees inactive companies in shared data', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);
    $company->update(['status' => 'inactive']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('companies.index', ['company' => $company->id]));

    $response->assertOk();
    $sharedCompanies = $response->baseResponse->original->getData()['page']['props']['userCompanies'];
    $ids = collect($sharedCompanies)->pluck('id');
    expect($ids)->toContain($company->id);
});

test('a system owner can access every company view', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);

    $session = ['current_company_id' => $company->id];

    actingAs($user)->withSession($session)->get(route('companies.index', ['company' => $company->id]))->assertOk();
    actingAs($user)->withSession($session)->get(route('companies.create', ['company' => $company->id]))->assertOk();
    actingAs($user)->withSession($session)->get(route('companies.show', ['company' => $company->id, 'id' => $company->id]))->assertOk();
    actingAs($user)->withSession($session)->get(route('companies.edit', ['company' => $company->id, 'id' => $company->id]))->assertOk();
});
