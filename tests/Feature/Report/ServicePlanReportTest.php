<?php

declare(strict_types=1);

use App\Modules\Plan\Models\Plan;
use App\Modules\Sale\Models\Sale;
use App\Modules\Service\Models\Service;

use function Pest\Laravel\actingAs;

test('a user without permission cannot view the service-plan report', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.service-plan.index', ['company' => $company->id]));

    $response->assertForbidden();
});

test('the service-plan report renders empty with the current-month range until a search is performed', function () {
    [$user, $company] = createUserWithCompany();

    Sale::factory()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.service-plan.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('reports/service-plan/index')
        ->where('searched', false)
        ->where('filters.group_by', 'service')
        ->where('filters.date_from', now()->startOfMonth()->toDateString())
        ->where('filters.date_to', now()->toDateString())
        ->has('rows', 0)
        ->where('meta.total', 0)
        ->where('summary.total_sales', 0)
        ->where('summary.total_revenue', 0)
    );
});

test('the service-plan report groups sales by service', function () {
    [$user, $company] = createUserWithCompany();

    $serviceA = Service::factory()->create(['company_id' => $company->id]);
    $serviceB = Service::factory()->create(['company_id' => $company->id]);

    Sale::factory()->create(['company_id' => $company->id, 'service_id' => $serviceA->id, 'price' => 100]);
    Sale::factory()->create(['company_id' => $company->id, 'service_id' => $serviceA->id, 'price' => 50]);
    Sale::factory()->create(['company_id' => $company->id, 'service_id' => $serviceB->id, 'price' => 30]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.service-plan.index', [
            'company' => $company->id,
            'group_by' => 'service',
            'searched' => '1',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->where('searched', true)
        ->has('rows', 2)
        ->where('rows.0.id', $serviceA->id)
        ->where('rows.0.sales_count', 2)
        ->where('rows.0.revenue', 150)
        ->where('rows.0.avg_ticket', 75)
        ->where('rows.1.id', $serviceB->id)
        ->where('rows.1.revenue', 30)
        ->where('summary.total_sales', 3)
        ->where('summary.total_revenue', 180)
        ->where('summary.avg_ticket', 60)
        ->where('summary.top_label', $serviceA->name)
        ->where('meta.total', 2)
    );
});

test('the service-plan report groups sales by plan with plan reference data', function () {
    [$user, $company] = createUserWithCompany();

    $service = Service::factory()->create(['company_id' => $company->id]);
    $planA = Plan::factory()->create([
        'company_id' => $company->id,
        'service_id' => $service->id,
        'sale_price' => 80,
        'roi_target_pct' => 40,
    ]);
    $planB = Plan::factory()->create([
        'company_id' => $company->id,
        'service_id' => $service->id,
        'sale_price' => 20,
        'roi_target_pct' => 10,
    ]);

    Sale::factory()->create(['company_id' => $company->id, 'service_id' => $service->id, 'plan_id' => $planA->id, 'price' => 90]);
    Sale::factory()->create(['company_id' => $company->id, 'service_id' => $service->id, 'plan_id' => $planB->id, 'price' => 25]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.service-plan.index', [
            'company' => $company->id,
            'group_by' => 'plan',
            'searched' => '1',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->has('rows', 2)
        ->where('rows.0.id', $planA->id)
        ->where('rows.0.revenue', 90)
        ->where('rows.0.sale_price', 80)
        ->where('rows.0.roi_target_pct', 40)
        ->where('summary.top_label', $planA->name)
    );
});

test('the service-plan report can be filtered by date range and capacity', function () {
    [$user, $company] = createUserWithCompany();

    $service = Service::factory()->create(['company_id' => $company->id]);

    Sale::factory()->create([
        'company_id' => $company->id,
        'service_id' => $service->id,
        'capacity' => Sale::CAPACITY_PROFILE,
        'price' => 100,
        'created_at' => '2026-03-10 12:00:00',
    ]);
    Sale::factory()->create([
        'company_id' => $company->id,
        'service_id' => $service->id,
        'capacity' => Sale::CAPACITY_FULL_ACCOUNT,
        'price' => 200,
        'created_at' => '2026-03-12 12:00:00',
    ]);
    Sale::factory()->create([
        'company_id' => $company->id,
        'service_id' => $service->id,
        'capacity' => Sale::CAPACITY_PROFILE,
        'price' => 999,
        'created_at' => '2026-05-01 12:00:00',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.service-plan.index', [
            'company' => $company->id,
            'group_by' => 'service',
            'capacity' => Sale::CAPACITY_PROFILE,
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
            'searched' => '1',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->has('rows', 1)
        ->where('rows.0.sales_count', 1)
        ->where('rows.0.revenue', 100)
        ->where('summary.total_revenue', 100)
        ->where('summary.profile_count', 1)
        ->where('summary.full_account_count', 0)
    );
});

test('the service-plan report only aggregates sales from the active company', function () {
    [$user, $company] = createUserWithCompany();

    $service = Service::factory()->create(['company_id' => $company->id]);

    Sale::factory()->create(['company_id' => $company->id, 'service_id' => $service->id, 'price' => 100]);
    Sale::factory()->count(3)->create(['price' => 500]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.service-plan.index', [
            'company' => $company->id,
            'searched' => '1',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->has('rows', 1)
        ->where('summary.total_sales', 1)
        ->where('summary.total_revenue', 100)
        ->where('meta.total', 1)
    );
});
