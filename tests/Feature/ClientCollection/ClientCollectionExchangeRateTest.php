<?php

declare(strict_types=1);

use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\Configuration\Models\Configuration;

use function Pest\Laravel\actingAs;

test('the collection freezes the catalog rate without the form sending it', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client);

    expect((float) $collection->exchange_rate)->toBe(36.5);
    expect($collection->currency)->toBe('USD');
    expect($collection->base_currency)->toBe('USD');
    expect((float) $collection->base_exchange_rate)->toBe(36.5);
    expect((float) $collection->amount_ves)->toBe(9125.0);
});

test('a collection in another currency also freezes the company rate', function () {
    [$user, $company, $client] = clientCollectionScenario();

    todayExchangeRate($company, $user, 'EUR', 40.0);

    $collection = createClientCollection($user, $company, $client, [
        'currency' => 'EUR',
        'amount' => 100,
    ]);

    expect($collection->currency)->toBe('EUR');
    expect((float) $collection->exchange_rate)->toBe(40.0);
    expect($collection->base_currency)->toBe('USD');
    expect((float) $collection->base_exchange_rate)->toBe(36.5);
    expect((float) $collection->amount_ves)->toBe(4000.0);
});

test('a currency without a loaded rate is rejected', function () {
    [$user, $company, $client] = clientCollectionScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-collections.store', ['company' => $company->id]),
            clientCollectionPayload($client, ['currency' => 'EUR']),
        )
        ->assertSessionHasErrors('exchange_rate');

    expect(ClientCollection::count())->toBe(0);
});

test('the rate typed in the form is ignored when the company does not allow overriding', function () {
    [$user, $company, $client] = clientCollectionScenario();

    Configuration::query()
        ->where('company_id', $company->id)
        ->update(['allows_rate_override' => 'no']);

    app()->forgetScopedInstances();

    $collection = createClientCollection($user, $company, $client, ['exchange_rate' => 99]);

    expect((float) $collection->exchange_rate)->toBe(36.5);
});

test('the rate typed in the form wins when the company allows overriding', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client, ['exchange_rate' => 40]);

    expect((float) $collection->exchange_rate)->toBe(40.0);
    expect((float) $collection->amount_ves)->toBe(10000.0);
});

test('saving the draft again refreshes the rate', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client);
    expect((float) $collection->exchange_rate)->toBe(36.5);

    \App\Modules\ExchangeRate\Models\ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 42.0]);

    app()->forgetScopedInstances();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-collections.update', ['company' => $company->id, 'id' => $collection->id]),
            clientCollectionPayload($client),
        )
        ->assertSessionHasNoErrors();

    expect((float) $collection->refresh()->exchange_rate)->toBe(42.0);
    expect((float) $collection->amount_ves)->toBe(10500.0);
});
