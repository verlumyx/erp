<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list only shows the advances of the active company', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    createSupplierAdvance($user, $company, $supplier);

    $otherCompany = Company::create([
        'name' => 'Otra empresa',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    SupplierAdvance::factory()->create([
        'company_id' => $otherCompany->id,
        'supplier_id' => Supplier::factory()->create(['company_id' => $otherCompany->id])->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-advances.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('supplier-advances/index')
            ->has('supplierAdvances', 1)
            ->where('meta.total', 1));
});

test('the list filters by supplier, status and payment method', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);

    createSupplierAdvance($user, $company, $supplier, ['payment_method' => 'transfer']);
    createSupplierAdvance($user, $company, $other, ['payment_method' => 'cash']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-advances.index', [
            'company' => $company->id,
            'supplier_id' => $supplier->id,
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('supplierAdvances', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-advances.index', [
            'company' => $company->id,
            'payment_method' => 'cash',
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('supplierAdvances', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-advances.index', [
            'company' => $company->id,
            'status' => 'confirmed',
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('supplierAdvances', 0));
});

test('the list filters by date range', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    /** Un anticipo con fecha vieja se valora con la tasa de su día, no con la de hoy. */
    ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => now()->subDays(10)->toDateString(),
        'rate' => 35.0,
        'type' => 'legal',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    createSupplierAdvance($user, $company, $supplier, [
        'advance_date' => now()->subDays(10)->toDateString(),
    ]);
    createSupplierAdvance($user, $company, $supplier);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-advances.index', [
            'company' => $company->id,
            'date_from' => now()->subDay()->toDateString(),
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('supplierAdvances', 1));
});

test('the lookup only offers the advances that still have credit', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    /** En borrador todavía no hay crédito que aplicar. */
    createSupplierAdvance($user, $company, $supplier);

    $delivered = confirmedSupplierAdvance($user, $company, $supplier, ['amount' => 400]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('supplier-advances.lookup', ['company' => $company->id, 'open' => 'yes']));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($delivered->id);
    expect((float) $response->json('data.0.meta.balance'))->toBe(400.0);
});

test('a user without permission cannot list the advances', function () {
    [$user, $company] = supplierAdvanceScenario();

    assignRoleWithPermissions($user, $company, ['suppliers.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-advances.index', ['company' => $company->id]))
        ->assertForbidden();
});
