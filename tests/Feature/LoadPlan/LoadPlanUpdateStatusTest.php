<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\Dispatch;
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

test('transitioning load plan to in_transit updates attached pending dispatches to confirmed', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    $dispatchA = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $route->id,
        'status' => 'pending',
    ]);

    $dispatchB = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $route->id,
        'status' => 'pending',
    ]);

    $plan = createLoadPlan($user, $company, $route, $warehouse, $vehicle, [
        'dispatches' => [
            ['dispatch_id' => $dispatchA->id, 'sequence' => 1],
            ['dispatch_id' => $dispatchB->id, 'sequence' => 2],
        ],
    ]);

    expect($dispatchA->refresh()->status)->toBe('pending');
    expect($dispatchB->refresh()->status)->toBe('pending');

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('load-plans.update-status', ['company' => $company->id, 'id' => $plan->id]), [
            'status' => 'in_transit',
        ])
        ->assertSessionHasNoErrors();

    $plan->refresh();
    expect($plan->status)->toBe('in_transit');
    expect($dispatchA->refresh()->status)->toBe('confirmed');
    expect($dispatchB->refresh()->status)->toBe('confirmed');
});

test('transitioning load plan to confirmed updates selected dispatches to delivered and unselected stay confirmed', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    $dispatchA = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $route->id,
        'status' => 'pending',
    ]);

    $dispatchB = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $route->id,
        'status' => 'pending',
    ]);

    $plan = createLoadPlan($user, $company, $route, $warehouse, $vehicle, [
        'dispatches' => [
            ['dispatch_id' => $dispatchA->id, 'sequence' => 1],
            ['dispatch_id' => $dispatchB->id, 'sequence' => 2],
        ],
    ]);

    // First go to in_transit
    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('load-plans.update-status', ['company' => $company->id, 'id' => $plan->id]), [
            'status' => 'in_transit',
        ])
        ->assertSessionHasNoErrors();

    expect($dispatchA->refresh()->status)->toBe('confirmed');
    expect($dispatchB->refresh()->status)->toBe('confirmed');

    // Confirm only dispatchA as delivered, dispatchB was returned/not delivered
    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('load-plans.update-status', ['company' => $company->id, 'id' => $plan->id]), [
            'status' => 'confirmed',
            'delivered_dispatch_ids' => [$dispatchA->id],
        ])
        ->assertSessionHasNoErrors();

    $plan->refresh();
    expect($plan->status)->toBe('confirmed');
    // dispatchA is delivered
    expect($dispatchA->refresh()->status)->toBe('delivered');
    // dispatchB stays confirmed ("Despachado") for return/devolución
    expect($dispatchB->refresh()->status)->toBe('confirmed');
});
