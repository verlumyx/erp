<?php

declare(strict_types=1);

use App\Modules\Configuration\Models\Configuration;
use App\Modules\Entry\Models\Entry;

use function Pest\Laravel\actingAs;

test('the entry freezes the catalog rate without the form sending it', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $payload = entryPayload($supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $entry = Entry::find($payload['id']);

    expect($entry->currency)->toBe('USD');
    expect((float) $entry->exchange_rate)->toBe(36.5);
    expect($entry->base_currency)->toBe('USD');
    expect((float) $entry->base_exchange_rate)->toBe(36.5);
});

test('an entry in another currency also freezes the company rate', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    todayExchangeRate($company, $user, 'EUR', 40.0);

    $payload = entryPayload($supplier, $warehouse, $item, $unit, ['currency' => 'EUR']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $entry = Entry::find($payload['id']);

    expect((float) $entry->exchange_rate)->toBe(40.0);
    expect($entry->base_currency)->toBe('USD');
    expect((float) $entry->base_exchange_rate)->toBe(36.5);
});

test('an entry in a currency without a loaded rate is not registered', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $payload = entryPayload($supplier, $warehouse, $item, $unit, ['currency' => 'EUR']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('exchange_rate');

    expect(Entry::find($payload['id']))->toBeNull();
});

test('the rate typed in the form is ignored when the company forbids correcting it', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    Configuration::query()
        ->where('company_id', $company->id)
        ->update(['allows_rate_override' => 'no']);

    $payload = entryPayload($supplier, $warehouse, $item, $unit, ['exchange_rate' => 1]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    expect((float) Entry::find($payload['id'])->exchange_rate)->toBe(36.5);
});

test('the rate typed in the form wins when the company allows correcting it', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $payload = entryPayload($supplier, $warehouse, $item, $unit, ['exchange_rate' => 38.25]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    expect((float) Entry::find($payload['id'])->exchange_rate)->toBe(38.25);
});

test('saving the draft again refreshes the rate', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit);
    expect((float) $entry->exchange_rate)->toBe(36.5);

    /** El resolver cachea por request: el test reusa la misma aplicación. */
    app()->forgetScopedInstances();

    \App\Modules\ExchangeRate\Models\ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 41.0]);

    $payload = entryPayload($supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('entries.update', ['company' => $company->id, 'id' => $entry->id]), $payload)
        ->assertSessionHasNoErrors();

    expect((float) $entry->refresh()->exchange_rate)->toBe(41.0);
});

test('confirming does not re-resolve the rate the entry already froze', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    app()->forgetScopedInstances();

    \App\Modules\ExchangeRate\Models\ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 41.0]);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    expect((float) $entry->refresh()->exchange_rate)->toBe(36.5);
});
