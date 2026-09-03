<?php

declare(strict_types=1);

use App\Modules\Store\Models\StoreSetting;

use function Pest\Laravel\getJson;

test('a request without the key header is forbidden', function () {
    storeScenario();

    getJson(route('api.store.settings'))
        ->assertForbidden()
        ->assertJsonPath('message', 'La tienda no está disponible.');
});

test('an invalid key is forbidden', function () {
    storeScenario();

    getJson(route('api.store.settings'), storeKeyHeaders('stk_wrong'))
        ->assertForbidden();
});

test('a disabled store is forbidden even with a valid key', function () {
    [, , $settings] = storeScenario();
    $settings->update(['is_enabled' => 'no']);

    getJson(route('api.store.settings'), storeKeyHeaders())
        ->assertForbidden();
});

test('an inactive company is forbidden', function () {
    [, $company] = storeScenario();
    $company->update(['status' => 'inactive']);

    getJson(route('api.store.settings'), storeKeyHeaders())
        ->assertForbidden();
});

test('a valid key serves the settings and records its last use', function () {
    [, , $settings] = storeScenario();

    expect($settings->api_key_last_used_at)->toBeNull();

    getJson(route('api.store.settings'), storeKeyHeaders())
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=60, public')
        ->assertJsonPath('data.store_name', $settings->store_name)
        ->assertJsonPath('data.brand_color', '#111827');

    expect($settings->fresh()->api_key_last_used_at)->not->toBeNull();
});

test('the last use is refreshed at most once per minute', function () {
    [, , $settings] = storeScenario();

    $settings->update(['api_key_last_used_at' => now()->subSeconds(10)]);
    $stamp = $settings->fresh()->api_key_last_used_at;

    $this->travel(20)->seconds();
    getJson(route('api.store.settings'), storeKeyHeaders())->assertOk();

    expect($settings->fresh()->api_key_last_used_at->equalTo($stamp))->toBeTrue();

    $this->travel(2)->minutes();
    getJson(route('api.store.settings'), storeKeyHeaders())->assertOk();

    expect($settings->fresh()->api_key_last_used_at->greaterThan($stamp))->toBeTrue();
});

test('the settings never expose the key hash', function () {
    storeScenario();

    $json = getJson(route('api.store.settings'), storeKeyHeaders())->json('data');

    expect($json)->not->toHaveKey('api_key_hash');
    expect(StoreSetting::first()->toArray())->not->toHaveKey('api_key_hash');
});
