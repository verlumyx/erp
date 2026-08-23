<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the detail carries the advance with its supplier and its mirror payment', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierAdvanceScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    $advance = createSupplierAdvance($user, $company, $supplier, [
        'purchase_order_id' => $order->id,
        'amount' => 400,
    ]);

    moveSupplierAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-advances.show', ['company' => $company->id, 'id' => $advance->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('supplier-advances/show')
            ->where('supplierAdvance.code', 'ANP000001')
            ->where('supplierAdvance.status', 'pending_confirmation')
            ->where('supplierAdvance.supplier_name', $supplier->name)
            ->where('supplierAdvance.purchase_order_code', $order->code)
            ->where('supplierAdvance.payment_code', 'PGP000001')
            ->where('supplierAdvance.payment_status', 'draft'));
});

test('an advance of another company is not reachable', function () {
    [$user, $company] = supplierAdvanceScenario();

    $otherCompany = Company::create([
        'name' => 'Otra empresa',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $advance = SupplierAdvance::factory()->create([
        'company_id' => $otherCompany->id,
        'supplier_id' => Supplier::factory()->create(['company_id' => $otherCompany->id])->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-advances.show', ['company' => $company->id, 'id' => $advance->id]))
        ->assertNotFound();
});

test('a user without permission cannot see the detail', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);

    assignRoleWithPermissions($user, $company, ['supplier-advances.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-advances.show', ['company' => $company->id, 'id' => $advance->id]))
        ->assertForbidden();
});
