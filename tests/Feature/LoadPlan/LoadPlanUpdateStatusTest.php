<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

test('status transitions from draft to in_transit and then confirmed', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    $plan = createLoadPlan($user, $company, $route, $warehouse, $vehicle);
    expect($plan->status)->toBe('draft');

    // 1. To in_transit
    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('load-plans.update-status', ['company' => $company->id, 'id' => $plan->id]), [
            'status' => 'in_transit',
        ])
        ->assertSessionHasNoErrors();

    $plan->refresh();
    expect($plan->status)->toBe('in_transit');

    // 2. To confirmed
    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('load-plans.update-status', ['company' => $company->id, 'id' => $plan->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $plan->refresh();
    expect($plan->status)->toBe('confirmed');
});

test('transition to invalid status fails validation or domain logic', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    $plan = createLoadPlan($user, $company, $route, $warehouse, $vehicle);

    // Invalid status string
    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('load-plans.update-status', ['company' => $company->id, 'id' => $plan->id]), [
            'status' => 'completed',
        ])
        ->assertSessionHasErrors(['status']);
});

test('confirmed load plan cannot be moved back to draft', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    $plan = createLoadPlan($user, $company, $route, $warehouse, $vehicle);
    $plan->update(['status' => 'confirmed']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('load-plans.update-status', ['company' => $company->id, 'id' => $plan->id]), [
            'status' => 'draft',
        ])
        ->assertSessionHasErrors(['status']);
});
