<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;

use function Pest\Laravel\actingAs;

/**
 * @param  array<string, mixed>  $query
 */
function lookupDispatches(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    array $query = [],
): \Illuminate\Testing\TestResponse {
    return actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('dispatches.lookup', ['company' => $company->id, ...$query]));
}

test('the lookup only offers dispatches a sales invoice can bill', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 100, 'unitCost' => 10]);

    /** Un borrador todavía no sacó nada: no se factura. */
    createDispatch($user, $company, $client, $warehouse, $item, $unit);

    $confirmed = createDispatch($user, $company, $client, $warehouse, $item, $unit);
    moveDispatchTo($user, $company, $confirmed, 'confirmed')->assertSessionHasNoErrors();

    $response = lookupDispatches($user, $company)->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($confirmed->id);
    expect($response->json('data.0.meta.recipient_id'))->toBe($client->id);
    expect($response->json('data.0.meta.lines'))->toHaveCount(1);
});

test('a rejected dispatch is not offered: the client kept nothing', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 100, 'unitCost' => 10]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);
    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    registerDispatchDelivery($user, $company, $dispatch->refresh(), [
        'delivery_status' => 'rejected',
        'rejection_reason' => 'Cliente cerrado.',
        'lines' => [['id' => $dispatch->lines()->first()->id, 'delivered_quantity' => 0]],
    ])->assertSessionHasNoErrors();

    expect(lookupDispatches($user, $company)->json('data'))->toHaveCount(0);
});

test('hydrating an already chosen dispatch ignores the filter', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $draft = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    $response = lookupDispatches($user, $company, ['ids' => $draft->id])->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($draft->id);
});

test('the lookup narrows down to the chosen client', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 100, 'unitCost' => 10]);

    $other = Client::factory()->create(['company_id' => $company->id]);

    $mine = createDispatch($user, $company, $client, $warehouse, $item, $unit);
    moveDispatchTo($user, $company, $mine, 'confirmed')->assertSessionHasNoErrors();

    $theirs = createDispatch($user, $company, $other, $warehouse, $item, $unit);
    moveDispatchTo($user, $company, $theirs, 'confirmed')->assertSessionHasNoErrors();

    $response = lookupDispatches($user, $company, ['recipient_id' => $other->id])->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($theirs->id);
});

test('the lookup requires the list permission', function () {
    [$user, $company] = dispatchScenario();

    assignRoleWithPermissions($user, $company, ['dispatches.show']);

    lookupDispatches($user, $company)->assertForbidden();
});
