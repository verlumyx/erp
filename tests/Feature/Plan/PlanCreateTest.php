<?php

declare(strict_types=1);

use App\Modules\Plan\Models\Plan;
use App\Modules\Service\Models\Service;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

function planPayload(Service $service, array $overrides = []): array
{
    return array_merge([
        'id' => (string) Str::uuid7(),
        'service_id' => $service->id,
        'name' => 'Netflix Mensual',
        'capacity' => 'profile',
        'duration_days' => 30,
        'sale_price' => 12.50,
        'roi_target_pct' => 40.0,
    ], $overrides);
}

test('a plan can be created', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('plans.store', ['company' => $company->id]), planPayload($service, ['id' => $id]));

    $response->assertRedirect(route('plans.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $plan = Plan::find($id);
    expect($plan)->not->toBeNull();
    expect($plan->name)->toBe('Netflix Mensual');
    expect($plan->capacity)->toBe('profile');
    expect($plan->duration_days)->toBe(30);
    expect($plan->active)->toBeTrue();
    expect($plan->company_id)->toBe($company->id);
    expect($plan->service_id)->toBe($service->id);
    expect($plan->code)->toBe('PLA000001');
});

test('the code auto-increments per company', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);

    $first = (string) Str::uuid7();
    $second = (string) Str::uuid7();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('plans.store', ['company' => $company->id]), planPayload($service, ['id' => $first]));
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('plans.store', ['company' => $company->id]), planPayload($service, ['id' => $second]));

    expect(Plan::find($first)->code)->toBe('PLA000001');
    expect(Plan::find($second)->code)->toBe('PLA000002');
});

test('each company has its own code sequence', function () {
    [$userA, $companyA] = createUserWithCompany();
    [$userB, $companyB] = createUserWithCompany();
    $serviceA = Service::factory()->create(['company_id' => $companyA->id]);
    $serviceB = Service::factory()->create(['company_id' => $companyB->id]);

    $idA = (string) Str::uuid7();
    $idB = (string) Str::uuid7();

    actingAs($userA)->withSession(['current_company_id' => $companyA->id])
        ->post(route('plans.store', ['company' => $companyA->id]), planPayload($serviceA, ['id' => $idA]));
    actingAs($userB)->withSession(['current_company_id' => $companyB->id])
        ->post(route('plans.store', ['company' => $companyB->id]), planPayload($serviceB, ['id' => $idB]));

    expect(Plan::find($idA)->code)->toBe('PLA000001');
    expect(Plan::find($idB)->code)->toBe('PLA000001');
});

test('the name is required', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('plans.store', ['company' => $company->id]), planPayload($service, ['name' => '']));

    $response->assertSessionHasErrors('name');
});

test('the capacity must be a valid value', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('plans.store', ['company' => $company->id]), planPayload($service, ['capacity' => 'shared']));

    $response->assertSessionHasErrors('capacity');
});

test('duration_days must be at least 1', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('plans.store', ['company' => $company->id]), planPayload($service, ['duration_days' => 0]));

    $response->assertSessionHasErrors('duration_days');
});

test('sale_price cannot be negative', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('plans.store', ['company' => $company->id]), planPayload($service, ['sale_price' => -1]));

    $response->assertSessionHasErrors('sale_price');
});

test('roi_target_pct cannot be negative', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('plans.store', ['company' => $company->id]), planPayload($service, ['roi_target_pct' => -5]));

    $response->assertSessionHasErrors('roi_target_pct');
});

test('the service must belong to the current company', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();
    $foreignService = Service::factory()->create(['company_id' => $otherCompany->id]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('plans.store', ['company' => $company->id]), planPayload($foreignService));

    $response->assertSessionHasErrors('service_id');
});

test('a user without permission cannot create a plan', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['plans.list']);
    $service = Service::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('plans.store', ['company' => $company->id]), planPayload($service));

    $response->assertForbidden();
});
