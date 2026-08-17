<?php

declare(strict_types=1);

use App\Modules\Currency\Models\Currency;
use App\Modules\Item\Models\Item;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\Supplier\Models\Supplier;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/**
 * Todo campo `currency` valida contra el catálogo global: ningún módulo
 * declara su propia lista de códigos.
 */
test('a supplier rejects a currency outside the catalog', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), supplierPayload([
            'currency' => 'XYZ',
        ]));

    $response->assertSessionHasErrors(['currency' => 'La moneda seleccionada no es válida.']);
    expect(Supplier::query()->count())->toBe(0);
});

test('a supplier accepts bolivares', function () {
    [$user, $company] = createUserWithCompany();

    $payload = supplierPayload(['currency' => 'VES']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), $payload);

    $response->assertSessionHasNoErrors();
    expect(Supplier::find($payload['id'])->currency)->toBe('VES');
});

test('a currency that is inactive is no longer accepted', function () {
    [$user, $company] = createUserWithCompany();

    Currency::query()->where('code', 'EUR')->update(['status' => 'inactive']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), supplierPayload([
            'currency' => 'EUR',
        ]));

    $response->assertSessionHasErrors('currency');
});

test('an item price rejects a currency outside the catalog', function () {
    [$user, $company, $unit] = itemScenario();

    $priceList = PriceList::factory()->create(['company_id' => $company->id]);

    $payload = itemPayload($unit, [
        'prices' => [
            [
                'price_list_id' => $priceList->id,
                'price' => 10,
                'currency' => 'XYZ',
            ],
        ],
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), $payload);

    $response->assertSessionHasErrors('prices.0.currency');
    expect(Item::query()->count())->toBe(0);
});

test('an item price accepts bolivares', function () {
    [$user, $company, $unit] = itemScenario();

    $priceList = PriceList::factory()->create(['company_id' => $company->id]);

    $payload = itemPayload($unit, [
        'prices' => [
            [
                'price_list_id' => $priceList->id,
                'price' => 10,
                'currency' => 'VES',
            ],
        ],
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), $payload);

    $response->assertSessionHasNoErrors();
    expect(Item::find($payload['id'])->prices->first()->currency)->toBe('VES');
});

/**
 * La tasa mide cuántos bolívares vale 1 unidad de la moneda extranjera, así
 * que el propio bolívar no lleva tasa: su valor es siempre 1.
 */
test('an exchange rate cannot be registered in bolivares', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('exchange-rates.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'currency' => 'VES',
            'rate_date' => '2026-08-15',
            'rate' => '36.5',
            'type' => 'legal',
        ]);

    $response->assertSessionHasErrors([
        'currency' => 'El bolívar no lleva tasa de cambio: su valor es siempre 1.',
    ]);
});

test('an exchange rate rejects a currency outside the catalog', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('exchange-rates.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'currency' => 'XYZ',
            'rate_date' => '2026-08-15',
            'rate' => '36.5',
            'type' => 'legal',
        ]);

    $response->assertSessionHasErrors('currency');
});
