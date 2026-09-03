<?php

declare(strict_types=1);

use App\Modules\ClientType\Models\ClientType;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\Store\Models\StoreSetting;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

function storeSettingsPayload(array $overrides = []): array
{
    return [
        'is_enabled' => 'yes',
        'store_name' => 'Ferretería El Tornillo',
        'brand_color' => '#0A84FF',
        'price_list_id' => null,
        'warehouse_id' => null,
        'shows_stock' => 'yes',
        'allows_orders' => 'no',
        'default_client_type_id' => null,
        'shows_secondary_currency' => 'yes',
        'contact_phone' => '+584121234567',
        'contact_email' => 'ventas@tornillo.com',
        'store_url' => 'https://tienda.tornillo.com',
        ...$overrides,
    ];
}

test('the settings are created on first visit with the company name', function () {
    [$user, $company] = createUserWithCompany();

    expect(StoreSetting::where('company_id', $company->id)->exists())->toBeFalse();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('store-settings.edit', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('store/settings/edit', false)
            ->where('settings.store_name', $company->name)
            ->where('settings.is_enabled', 'no')
            ->where('settings.has_api_key', false)
            ->has('options.price_lists')
            ->has('options.warehouses')
            ->has('options.client_types'));

    expect(StoreSetting::where('company_id', $company->id)->count())->toBe(1);
});

test('the settings can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $priceList = PriceList::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    $clientType = ClientType::factory()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-settings.update', ['company' => $company->id]), storeSettingsPayload([
            'price_list_id' => $priceList->id,
            'warehouse_id' => $warehouse->id,
            'default_client_type_id' => $clientType->id,
        ]))
        ->assertRedirect(route('store-settings.edit', ['company' => $company->id]))
        ->assertSessionHasNoErrors();

    $settings = StoreSetting::where('company_id', $company->id)->firstOrFail();

    expect($settings->is_enabled)->toBe('yes')
        ->and($settings->store_name)->toBe('Ferretería El Tornillo')
        ->and($settings->brand_color)->toBe('#0a84ff')
        ->and($settings->price_list_id)->toBe($priceList->id)
        ->and($settings->warehouse_id)->toBe($warehouse->id)
        ->and($settings->default_client_type_id)->toBe($clientType->id)
        ->and($settings->shows_stock)->toBe('yes')
        ->and($settings->store_url)->toBe('https://tienda.tornillo.com');
});

test('the price list and warehouse must belong to the company and be active', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();

    $foreignList = PriceList::factory()->create(['company_id' => $otherCompany->id]);
    $inactiveWarehouse = Warehouse::factory()->create(['company_id' => $company->id, 'status' => 'inactive']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-settings.update', ['company' => $company->id]), storeSettingsPayload([
            'price_list_id' => $foreignList->id,
            'warehouse_id' => $inactiveWarehouse->id,
        ]))
        ->assertSessionHasErrors(['price_list_id', 'warehouse_id']);
});

test('the brand color must be a hex color', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-settings.update', ['company' => $company->id]), storeSettingsPayload([
            'brand_color' => 'blue',
        ]))
        ->assertSessionHasErrors(['brand_color']);
});

test('the logo is stored on the public disk and can be removed', function () {
    Storage::fake('public');
    [$user, $company] = createUserWithCompany();

    $logo = new UploadedFile(base_path('tests/Fixtures/store/photo.png'), 'logo.png', 'image/png', null, true);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-settings.update', ['company' => $company->id]), storeSettingsPayload(['logo' => $logo]))
        ->assertSessionHasNoErrors();

    $settings = StoreSetting::where('company_id', $company->id)->firstOrFail();
    expect($settings->logo_path)->toBe("store/{$company->id}/logo.png");
    Storage::disk('public')->assertExists($settings->logo_path);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-settings.update', ['company' => $company->id]), storeSettingsPayload(['remove_logo' => 'yes']))
        ->assertSessionHasNoErrors();

    expect($settings->fresh()->logo_path)->toBeNull();
});

test('generating a key stores only its hash and shows the key once', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('store-settings.generate-key', ['company' => $company->id]));

    $response->assertRedirect(route('store-settings.edit', ['company' => $company->id]));
    $plainKey = session('store_api_key');

    expect($plainKey)->toStartWith(StoreSetting::KEY_PREFIX);

    $settings = StoreSetting::where('company_id', $company->id)->firstOrFail();
    expect($settings->api_key_hash)->toBe(hash('sha256', $plainKey))
        ->and($settings->api_key_hash)->not->toBe($plainKey);
});

test('generating another key invalidates the previous one', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('store-settings.generate-key', ['company' => $company->id]));

    $firstHash = StoreSetting::where('company_id', $company->id)->value('api_key_hash');

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('store-settings.generate-key', ['company' => $company->id]));

    $secondHash = StoreSetting::where('company_id', $company->id)->value('api_key_hash');

    expect($secondHash)->not->toBe($firstHash);
    expect(StoreSetting::where('api_key_hash', $firstHash)->exists())->toBeFalse();
});

test('a user without the permission cannot open the settings', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['store-items.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('store-settings.edit', ['company' => $company->id]))
        ->assertForbidden();
});
