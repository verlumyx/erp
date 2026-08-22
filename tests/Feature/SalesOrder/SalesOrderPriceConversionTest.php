<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemPrice;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\User\Models\User;

use function Pest\Laravel\actingAs;

/**
 * Cada lista de precio lleva su propia moneda, independiente de la del pedido.
 * Al capturar la línea el precio se convierte una vez y queda congelado: la
 * línea ya no recuerda de qué moneda venía.
 */
function priceListWith(
    Company $company,
    User $user,
    Item $item,
    float $price,
    string $currency,
): PriceList {
    $priceList = PriceList::factory()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
    ]);

    ItemPrice::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'price_list_id' => $priceList->id,
        'price' => $price,
        'currency' => $currency,
        'status' => 'active',
    ]);

    return $priceList;
}

test('a price listed in another currency is converted to the currency of the order', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    todayExchangeRate($company, $user, 'EUR', 40.0);
    $priceList = priceListWith($company, $user, $item, 100.0, 'EUR');

    /** El formulario manda 0: sin precio pactado manda la lista. */
    $payload = salesOrderPayload($client, $warehouse, $item, $unit, [
        'price_list_id' => $priceList->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 0,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = SalesOrder::with('lines')->find($payload['id'])->lines->first();

    /** 100 EUR × 40,00 = 4.000 Bs; 4.000 ÷ 36,50 = 109,589041 USD. */
    expect((float) $line->list_price)->toBe(109.589041);
    expect((float) $line->unit_price)->toBe(109.589041);
    expect((float) $line->subtotal)->toBe(219.18);
});

test('a price listed in the currency of the order travels untouched', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $priceList = priceListWith($company, $user, $item, 100.0, 'USD');

    $payload = salesOrderPayload($client, $warehouse, $item, $unit, [
        'price_list_id' => $priceList->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 0,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = SalesOrder::with('lines')->find($payload['id'])->lines->first();

    expect((float) $line->unit_price)->toBe(100.0);
});

test('a price agreed by the salesperson beats the one from the list', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    todayExchangeRate($company, $user, 'EUR', 40.0);
    $priceList = priceListWith($company, $user, $item, 100.0, 'EUR');

    $payload = salesOrderPayload($client, $warehouse, $item, $unit, [
        'price_list_id' => $priceList->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            /** Precio pactado: distinto del de lista que ve la pantalla. */
            'unit_price' => 150,
            'list_price' => 109.589041,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = SalesOrder::with('lines')->find($payload['id'])->lines->first();

    expect((float) $line->unit_price)->toBe(150.0);
    /** El de lista se guarda igual: es contra el que se mide el descuento. */
    expect((float) $line->list_price)->toBe(109.589041);
});

/**
 * Sin tasa no hay conversión posible, y una lista en euros valorada como si
 * fuera dólares es exactamente el desastre que este módulo evita.
 */
test('an order priced from a list without a loaded rate is not issued', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $priceList = priceListWith($company, $user, $item, 100.0, 'EUR');

    $payload = salesOrderPayload($client, $warehouse, $item, $unit, [
        'price_list_id' => $priceList->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 0,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('exchange_rate');

    expect(SalesOrder::find($payload['id']))->toBeNull();
});

/**
 * El precio se congela al capturarlo: reabrir el borrador con otra tasa lo
 * revalúa, porque el borrador vuelve a resolver todo contra el catálogo.
 */
test('saving the draft again reprices the line with the fresh rate', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $rate = todayExchangeRate($company, $user, 'EUR', 40.0);
    $priceList = priceListWith($company, $user, $item, 100.0, 'EUR');

    $overrides = [
        'price_list_id' => $priceList->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 0,
        ]],
    ];

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, $overrides);

    $rate->update(['rate' => 44.0]);
    app()->forgetScopedInstances();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-orders.update', ['company' => $company->id, 'id' => $order->id]),
            salesOrderPayload($client, $warehouse, $item, $unit, $overrides),
        )
        ->assertSessionHasNoErrors();

    /** 100 EUR × 44,00 = 4.400 Bs; 4.400 ÷ 36,50 = 120,547945 USD. */
    expect((float) SalesOrder::with('lines')->find($order->id)->lines->first()->unit_price)
        ->toBe(120.547945);
});
