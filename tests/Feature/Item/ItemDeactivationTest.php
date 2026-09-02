<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;

use function Pest\Laravel\actingAs;

function deactivateItem(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    Item $item,
): \Illuminate\Testing\TestResponse {
    return actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('items.update-status', ['company' => $company->id, 'id' => $item->id]),
            ['status' => 'inactive'],
        );
}

test('an item with no stock and no open documents can be deactivated', function () {
    [$user, $company, , , $item] = purchaseReturnScenario();

    deactivateItem($user, $company, $item)->assertSessionHasNoErrors();

    expect($item->refresh()->status)->toBe('inactive');
});

test('an item with stock cannot be deactivated', function () {
    [$user, $company, , $warehouse, $item, , $location] = purchaseReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 5, 'unitCost' => 10]);

    deactivateItem($user, $company, $item)->assertSessionHasErrors('status');

    expect($item->refresh()->status)->toBe('active');
});

test('an item awaited by an open order cannot be deactivated', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    deactivateItem($user, $company, $item)->assertSessionHasErrors('status');

    expect($item->refresh()->status)->toBe('active');
});
