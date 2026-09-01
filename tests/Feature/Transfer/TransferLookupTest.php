<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

/**
 * @param  array<string, mixed>  $query
 */
function lookupTransfers(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    array $query = [],
): \Illuminate\Testing\TestResponse {
    return actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('transfers.lookup', ['company' => $company->id, ...$query]));
}

test('the lookup returns transfers as options with their lines', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    $response = lookupTransfers($user, $company)->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($transfer->id);
    expect($response->json('data.0.label'))->toContain('TRA000001');
    expect($response->json('data.0.meta.origin_warehouse_id'))->toBe($origin->id);
    expect($response->json('data.0.meta.destination_warehouse_id'))->toBe($destination->id);
    expect($response->json('data.0.meta.lines'))->toHaveCount(1);
});

test('the lookup searches by code', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    createTransfer($user, $company, $origin, $destination, $item, $unit);
    createTransfer($user, $company, $origin, $destination, $item, $unit);

    $response = lookupTransfers($user, $company, ['q' => 'TRA000002'])->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.meta.code'))->toBe('TRA000002');
});

test('the lookup hydrates what a form already had by ids', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);
    createTransfer($user, $company, $origin, $destination, $item, $unit);

    $response = lookupTransfers($user, $company, ['ids' => $transfer->id])->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($transfer->id);
});

test('the lookup pages its results', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    createTransfer($user, $company, $origin, $destination, $item, $unit);
    createTransfer($user, $company, $origin, $destination, $item, $unit);
    createTransfer($user, $company, $origin, $destination, $item, $unit);

    $response = lookupTransfers($user, $company, ['per_page' => 2, 'page' => 1])->assertOk();

    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('has_more'))->toBeTrue();
});

test('the lookup needs the list permission', function () {
    [$user, $company] = transferScenario();

    restrictPermissions($user, $company, ['transfers.show']);

    lookupTransfers($user, $company)->assertForbidden();
});
