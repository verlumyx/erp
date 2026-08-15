<?php

declare(strict_types=1);

use App\Modules\Plan\Models\Plan;
use App\Modules\Service\Models\Service;

use function Pest\Laravel\actingAs;

test('a plan can be updated', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);
    $other = Service::factory()->create(['company_id' => $company->id]);

    $plan = Plan::factory()->create([
        'company_id' => $company->id,
        'service_id' => $service->id,
        'name' => 'Netflix Mensual',
        'capacity' => 'profile',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('plans.update', ['company' => $company->id, 'id' => $plan->id]), [
            'service_id' => $other->id,
            'name' => 'Netflix Trimestral',
            'capacity' => 'full_account',
            'duration_days' => 90,
            'sale_price' => 30,
            'roi_target_pct' => 55,
        ]);

    $response->assertRedirect(route('plans.show', ['company' => $company->id, 'id' => $plan->id]));
    $response->assertSessionHasNoErrors();

    $fresh = Plan::find($plan->id);
    expect($fresh->name)->toBe('Netflix Trimestral');
    expect($fresh->capacity)->toBe('full_account');
    expect($fresh->duration_days)->toBe(90);
    expect($fresh->service_id)->toBe($other->id);
});

test('a plan from another company cannot be updated', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);
    $otherService = Service::factory()->create(['company_id' => $otherCompany->id]);

    $plan = Plan::factory()->create(['company_id' => $otherCompany->id, 'service_id' => $otherService->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('plans.update', ['company' => $company->id, 'id' => $plan->id]), [
            'service_id' => $service->id,
            'name' => 'Hacked',
            'capacity' => 'profile',
            'duration_days' => 30,
            'sale_price' => 10,
            'roi_target_pct' => 10,
        ]);

    $response->assertNotFound();
});

test('a user without permission cannot update a plan', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['plans.list']);
    $service = Service::factory()->create(['company_id' => $company->id]);
    $plan = Plan::factory()->create(['company_id' => $company->id, 'service_id' => $service->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('plans.update', ['company' => $company->id, 'id' => $plan->id]), [
            'service_id' => $service->id,
            'name' => 'Nope',
            'capacity' => 'profile',
            'duration_days' => 30,
            'sale_price' => 10,
            'roi_target_pct' => 10,
        ]);

    $response->assertForbidden();
});
