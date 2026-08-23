<?php

declare(strict_types=1);

use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\Supplier\Models\Supplier;

use function Pest\Laravel\actingAs;

/** Una devolución de la empresa activa lista para acreditarse. */
function creditableReturn(
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    array $overrides = [],
): PurchaseReturn {
    return PurchaseReturn::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        ...$overrides,
    ]);
}

test('the lookup only offers returns that can still be credited', function () {
    [$user, $company, $supplier, $warehouse] = purchaseReturnScenario();

    $creditable = creditableReturn($company, $supplier, $warehouse);

    /** En borrador la mercancía no ha salido. */
    PurchaseReturn::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    /** Anulada tampoco hay qué acreditar. */
    PurchaseReturn::factory()->cancelled()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-returns.lookup', ['company' => $company->id]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($creditable->id);
    expect($response->json('data.0.meta.supplier_id'))->toBe($supplier->id);
    expect($response->json('data.0.meta.supplier_name'))->toBe($supplier->name);
});

test('the lookup can be narrowed to one supplier', function () {
    [$user, $company, $supplier, $warehouse] = purchaseReturnScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);

    creditableReturn($company, $supplier, $warehouse);
    $mine = creditableReturn($company, $other, $warehouse);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-returns.lookup', [
            'company' => $company->id,
            'supplier_id' => $other->id,
        ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($mine->id);
});

test('the lookup searches by code and by tracking number', function () {
    [$user, $company, $supplier, $warehouse] = purchaseReturnScenario();

    $tracked = creditableReturn($company, $supplier, $warehouse, [
        'tracking_number' => 'ZM-777777',
    ]);

    creditableReturn($company, $supplier, $warehouse);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-returns.lookup', [
            'company' => $company->id,
            'q' => '777777',
        ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($tracked->id);

    $byCode = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-returns.lookup', [
            'company' => $company->id,
            'q' => $tracked->code,
        ]));

    expect($byCode->json('data.0.value'))->toBe($tracked->id);
});

/**
 * Hidratar lo ya elegido no filtra por estado: la devolución que una nota
 * acredita ya no es «acreditable», pero sigue siendo la suya.
 */
test('hydrating by ids returns an already credited return', function () {
    [$user, $company, $supplier, $warehouse] = purchaseReturnScenario();

    $credited = PurchaseReturn::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'completed',
        'credit_note_id' => \App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote::factory()->create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
        ])->id,
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-returns.lookup', [
            'company' => $company->id,
            'ids' => $credited->id,
        ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($credited->id);
});

test('the lookup never crosses companies', function () {
    [$user, $company] = purchaseReturnScenario();

    PurchaseReturn::factory()->confirmed()->create();

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-returns.lookup', ['company' => $company->id]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(0);
});

test('a user without permission cannot use the lookup', function () {
    [$user, $company] = purchaseReturnScenario();
    assignRoleWithPermissions($user, $company, ['suppliers.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-returns.lookup', ['company' => $company->id]))
        ->assertForbidden();
});
