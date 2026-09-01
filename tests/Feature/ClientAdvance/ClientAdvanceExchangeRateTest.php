<?php

declare(strict_types=1);

use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\Configuration\Models\Configuration;
use App\Modules\ExchangeRate\Models\ExchangeRate;

use function Pest\Laravel\actingAs;

test('the advance freezes the catalog rate without the form sending it', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);

    expect($advance->currency)->toBe('USD');
    expect((float) $advance->exchange_rate)->toBe(36.5);
    expect($advance->base_currency)->toBe('USD');
    expect((float) $advance->base_exchange_rate)->toBe(36.5);
});

test('an advance in another currency also freezes the company rate', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    todayExchangeRate($company, $user, 'EUR', 40.0);

    $advance = createClientAdvance($user, $company, $client, [
        'currency' => 'EUR',
        'amount' => 100,
    ]);

    expect($advance->currency)->toBe('EUR');
    expect((float) $advance->exchange_rate)->toBe(40.0);
    expect($advance->base_currency)->toBe('USD');
    expect((float) $advance->base_exchange_rate)->toBe(36.5);
});

test('a currency without a loaded rate is rejected', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-advances.store', ['company' => $company->id]),
            clientAdvancePayload($client, ['currency' => 'EUR']),
        )
        ->assertSessionHasErrors('exchange_rate');

    expect(ClientAdvance::count())->toBe(0);
});

test('the rate typed in the form is ignored when the company does not allow overriding', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    Configuration::query()
        ->where('company_id', $company->id)
        ->update(['allows_rate_override' => 'no']);

    app()->forgetScopedInstances();

    $advance = createClientAdvance($user, $company, $client, ['exchange_rate' => 99]);

    expect((float) $advance->exchange_rate)->toBe(36.5);
});

test('the rate typed in the form wins when the company allows overriding', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client, ['exchange_rate' => 40]);

    expect((float) $advance->exchange_rate)->toBe(40.0);
});

test('saving the draft again refreshes the rate', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);
    expect((float) $advance->exchange_rate)->toBe(36.5);

    ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 42.0]);

    app()->forgetScopedInstances();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-advances.update', ['company' => $company->id, 'id' => $advance->id]),
            clientAdvancePayload($client),
        )
        ->assertSessionHasNoErrors();

    expect((float) $advance->refresh()->exchange_rate)->toBe(42.0);
});

/**
 * El cobro espejo copia la tasa del anticipo, no la del día en que se aprueba:
 * el dinero comprometido vale lo que valía cuando se capturó.
 */
test('the mirror collection copies the frozen rate of the advance', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client, ['amount' => 400]);

    ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 99.0]);

    app()->forgetScopedInstances();

    moveClientAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    $collection = ClientCollection::query()
        ->where('origin_id', $advance->id)
        ->firstOrFail();

    expect((float) $collection->exchange_rate)->toBe(36.5);
    expect($collection->base_currency)->toBe('USD');
    expect((float) $collection->base_exchange_rate)->toBe(36.5);
    /** El cobro tiene valor legal: su importe en bolívares queda escrito. */
    expect((float) $collection->amount_ves)->toBe(14600.0);
});
