<?php

declare(strict_types=1);

use App\Modules\Warehouse\Models\Warehouse;

use function Pest\Laravel\actingAs;

function deactivateWarehouse(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    Warehouse $warehouse,
): \Illuminate\Testing\TestResponse {
    return actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('warehouses.update-status', ['company' => $company->id, 'id' => $warehouse->id]),
            ['status' => 'inactive'],
        );
}

test('an empty warehouse can be deactivated', function () {
    [$user, $company, , $warehouse] = purchaseReturnScenario();

    deactivateWarehouse($user, $company, $warehouse)->assertSessionHasNoErrors();

    expect($warehouse->refresh()->status)->toBe('inactive');
});

test('a warehouse with goods inside cannot be deactivated', function () {
    [$user, $company, , $warehouse, $item, , $location] = purchaseReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 5, 'unitCost' => 10]);

    deactivateWarehouse($user, $company, $warehouse)->assertSessionHasErrors('status');

    expect($warehouse->refresh()->status)->toBe('active');
});

test('a warehouse with a pending order cannot be deactivated', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    deactivateWarehouse($user, $company, $warehouse)->assertSessionHasErrors('status');

    expect($warehouse->refresh()->status)->toBe('active');
});
