<?php

use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\ClientType\Models\ClientType;
use App\Modules\Item\Models\ItemPrice;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Models\StoreOrder;
use App\Modules\Store\Models\StoreOrderLine;
use App\Modules\Tax\Models\Tax;

use function Pest\Laravel\actingAs;

/** Pedido web pendiente de un comprador dado, con una línea del producto del escenario. */
function webOrderFor(array $scenario, StoreCustomer $customer, array $overrides = []): StoreOrder
{
    $order = StoreOrder::factory()->create([
        'company_id' => $scenario['company']->id,
        'store_customer_id' => $customer->id,
        'client_id' => $customer->client_id,
        'currency' => 'USD',
        'subtotal' => 90,
        'total' => 90,
        ...$overrides,
    ]);

    StoreOrderLine::factory()->create([
        'store_order_id' => $order->id,
        'company_id' => $scenario['company']->id,
        'item_id' => $scenario['item']->id,
        'store_item_id' => $scenario['store_item']->id,
        'measurement_unit_id' => $scenario['unit']->id,
        'quantity' => 2,
        'unit_price' => 45,
        'list_price' => 45,
        'subtotal' => 90,
        'total' => 90,
    ]);

    return $order->fresh();
}

function convertWebOrder(array $scenario, StoreOrder $order, array $payload = [])
{
    return actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->from(route('store-orders.show', ['company' => $scenario['company']->id, 'id' => $order->id]))
        ->put(route('store-orders.convert', ['company' => $scenario['company']->id, 'id' => $order->id]), $payload);
}

test('un comprador vinculado convierte directo y la orden nace en borrador con las líneas', function () {
    $scenario = storeOrderScenario();

    $tax = Tax::factory()->create(['company_id' => $scenario['company']->id, 'percentage' => 16]);
    $scenario['item']->update(['sale_tax_id' => $tax->id]);

    $client = Client::factory()->create([
        'company_id' => $scenario['company']->id,
        'payment_term_days' => 15,
    ]);
    $customer = StoreCustomer::factory()->linkedTo($client->id)->create(['company_id' => $scenario['company']->id]);
    $order = webOrderFor($scenario, $customer);

    $response = convertWebOrder($scenario, $order);

    $response->assertSessionHasNoErrors();

    $order->refresh();
    $salesOrder = SalesOrder::query()->with('lines')->findOrFail($order->sales_order_id);

    $response->assertRedirect(route('sales-orders.show', ['company' => $scenario['company']->id, 'id' => $salesOrder->id]));

    expect($order->status)->toBe('converted')
        ->and($order->client_id)->toBe($client->id)
        ->and($order->converted_by)->toBe($scenario['user']->id)
        ->and($order->converted_at)->not->toBeNull()
        ->and($salesOrder->status)->toBe('draft')
        ->and($salesOrder->client_id)->toBe($client->id)
        ->and($salesOrder->warehouse_id)->toBe($scenario['warehouse']->id)
        ->and($salesOrder->price_list_id)->toBe($scenario['price_list']->id)
        ->and($salesOrder->salesperson_id)->toBe($scenario['user']->id)
        ->and($salesOrder->payment_term_days)->toBe(15)
        ->and($salesOrder->client_reference)->toBe($order->code)
        ->and($salesOrder->lines)->toHaveCount(1);

    $line = $salesOrder->lines->first();

    expect($line->item_id)->toBe($scenario['item']->id)
        ->and((float) $line->quantity)->toBe(2.0)
        ->and((float) $line->unit_price)->toBe(45.0)
        ->and($line->tax_id)->toBe($tax->id)
        ->and((float) $line->tax_percent)->toBe(16.0)
        ->and((float) $line->tax_amount)->toBe(14.4);

    $address = ClientAddress::query()->where('client_id', $client->id)->first();

    expect($address)->not->toBeNull()
        ->and($address->address)->toBe($order->delivery_address)
        ->and($salesOrder->client_address_id)->toBe($address->id);
});

test('la lista del cliente manda sobre la de la tienda y avisa si el precio cambió', function () {
    $scenario = storeOrderScenario();

    $clientList = PriceList::factory()->create(['company_id' => $scenario['company']->id]);
    ItemPrice::factory()->create([
        'company_id' => $scenario['company']->id,
        'item_id' => $scenario['item']->id,
        'price_list_id' => $clientList->id,
        'price' => 40,
        'currency' => 'USD',
    ]);

    $client = Client::factory()->create([
        'company_id' => $scenario['company']->id,
        'price_list_id' => $clientList->id,
    ]);
    $customer = StoreCustomer::factory()->linkedTo($client->id)->create(['company_id' => $scenario['company']->id]);
    $order = webOrderFor($scenario, $customer);

    $response = convertWebOrder($scenario, $order);

    $response->assertSessionHasNoErrors();

    $salesOrder = SalesOrder::query()->with('lines')->findOrFail($order->fresh()->sales_order_id);

    expect($salesOrder->price_list_id)->toBe($clientList->id)
        ->and((float) $salesOrder->lines->first()->unit_price)->toBe(40.0)
        ->and(session('success'))->toContain('cambió');
});

test('sin vínculo exige cliente y guarda el vínculo con conversion', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);
    $order = webOrderFor($scenario, $customer);

    convertWebOrder($scenario, $order)->assertSessionHasErrors(['client_id']);

    expect($order->fresh()->status)->toBe('pending');

    $client = Client::factory()->create(['company_id' => $scenario['company']->id]);

    convertWebOrder($scenario, $order, ['client_id' => $client->id])->assertSessionHasNoErrors();

    $customer->refresh();

    expect($customer->client_id)->toBe($client->id)
        ->and($customer->link_source)->toBe('conversion')
        ->and($customer->linked_by)->toBe($scenario['user']->id)
        ->and($order->fresh()->client_id)->toBe($client->id);
});

test('crear el cliente usa los defaults de Ajustes y llama a ClientCreateService', function () {
    $scenario = storeOrderScenario();

    $type = ClientType::factory()->create(['company_id' => $scenario['company']->id]);
    $scenario['settings']->update(['default_client_type_id' => $type->id]);

    $customer = StoreCustomer::factory()->create([
        'company_id' => $scenario['company']->id,
        'email' => 'nuevo@example.com',
        'phone' => '+58 424 111 2222',
    ]);
    $order = webOrderFor($scenario, $customer);

    $this->mock(\App\Modules\Client\Services\ClientCreateService::class)
        ->shouldReceive('execute')
        ->once()
        ->withArgs(fn (\App\Modules\Client\Commands\CreateClientCommand $command): bool => $command->clientTypeId === $type->id
            && $command->priceListId === $scenario['price_list']->id
            && $command->documentType === 'J'
            && $command->documentNumber === '123456789'
            && $command->email === 'nuevo@example.com'
            && $command->paymentTermDays === 0
            && $command->creditLimit === '0'
            && $command->salespersonId === $scenario['user']->id)
        ->andReturnUsing(fn (\App\Modules\Client\Commands\CreateClientCommand $command): Client => Client::factory()->create([
            'id' => $command->id,
            'company_id' => $scenario['company']->id,
            'client_type_id' => $type->id,
            'price_list_id' => $command->priceListId,
            'document_type' => 'J',
            'document_number' => '123456789',
            'email' => 'nuevo@example.com',
        ]));

    convertWebOrder($scenario, $order, [
        'create_client' => 'yes',
        'document_type' => 'J',
        'document_number' => '123456789',
    ])->assertSessionHasNoErrors();

    $customer->refresh();

    expect($customer->document_type)->toBe('J')
        ->and($customer->document_number)->toBe('123456789')
        ->and($customer->client_id)->not->toBeNull()
        ->and($customer->link_source)->toBe('conversion')
        ->and($order->fresh()->status)->toBe('converted');
});

test('crear el cliente de verdad deja un cliente con los datos del comprador', function () {
    $scenario = storeOrderScenario();

    $type = ClientType::factory()->create(['company_id' => $scenario['company']->id]);
    $scenario['settings']->update(['default_client_type_id' => $type->id]);

    $customer = StoreCustomer::factory()->withDocument('V', '19000111')->create([
        'company_id' => $scenario['company']->id,
        'name' => 'Ana Pérez',
    ]);
    $order = webOrderFor($scenario, $customer);

    convertWebOrder($scenario, $order, ['create_client' => 'yes'])->assertSessionHasNoErrors();

    $client = Client::query()->where('document_number', '19000111')->firstOrFail();

    expect($client->name)->toBe('Ana Pérez')
        ->and($client->code)->toBe('CLI000001')
        ->and($client->client_type_id)->toBe($type->id)
        ->and($client->price_list_id)->toBe($scenario['price_list']->id)
        ->and($client->salesperson_id)->toBe($scenario['user']->id)
        ->and($customer->fresh()->client_id)->toBe($client->id);
});

test('sin default_client_type_id no se puede crear el cliente', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->withDocument('V', '19000111')->create(['company_id' => $scenario['company']->id]);
    $order = webOrderFor($scenario, $customer);

    convertWebOrder($scenario, $order, ['create_client' => 'yes'])
        ->assertSessionHasErrors(['default_client_type_id']);

    expect($order->fresh()->status)->toBe('pending')
        ->and(Client::query()->count())->toBe(0)
        ->and(SalesOrder::query()->count())->toBe(0);
});

test('crear el cliente sin RIF exige el RIF', function () {
    $scenario = storeOrderScenario();

    $type = ClientType::factory()->create(['company_id' => $scenario['company']->id]);
    $scenario['settings']->update(['default_client_type_id' => $type->id]);

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);
    $order = webOrderFor($scenario, $customer);

    convertWebOrder($scenario, $order, ['create_client' => 'yes'])
        ->assertSessionHasErrors(['document_number']);
});

test('todo ocurre en una transacción: si la orden falla no queda vínculo ni cliente', function () {
    $scenario = storeOrderScenario();

    $scenario['warehouse']->update(['status' => 'inactive']);
    $scenario['settings']->update(['warehouse_id' => null]);

    $client = Client::factory()->create(['company_id' => $scenario['company']->id]);
    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);
    $order = webOrderFor($scenario, $customer);

    convertWebOrder($scenario, $order, ['client_id' => $client->id])
        ->assertSessionHasErrors(['warehouse_id']);

    expect($customer->fresh()->client_id)->toBeNull()
        ->and($order->fresh()->status)->toBe('pending')
        ->and(SalesOrder::query()->count())->toBe(0);
});

test('sin bodega en Ajustes usa la primera bodega activa', function () {
    $scenario = storeOrderScenario();
    $scenario['settings']->update(['warehouse_id' => null]);

    $client = Client::factory()->create(['company_id' => $scenario['company']->id]);
    $customer = StoreCustomer::factory()->linkedTo($client->id)->create(['company_id' => $scenario['company']->id]);
    $order = webOrderFor($scenario, $customer);

    convertWebOrder($scenario, $order)->assertSessionHasNoErrors();

    expect(SalesOrder::query()->firstOrFail()->warehouse_id)->toBe($scenario['warehouse']->id);
});

test('la dirección elegida del cliente se usa sin crear otra', function () {
    $scenario = storeOrderScenario();

    $client = Client::factory()->create(['company_id' => $scenario['company']->id]);
    $address = ClientAddress::factory()->create(['company_id' => $scenario['company']->id, 'client_id' => $client->id]);
    $customer = StoreCustomer::factory()->linkedTo($client->id)->create(['company_id' => $scenario['company']->id]);
    $order = webOrderFor($scenario, $customer, [
        'client_address_id' => $address->id,
        'delivery_address' => null,
        'delivery_city' => null,
    ]);

    convertWebOrder($scenario, $order)->assertSessionHasNoErrors();

    expect(SalesOrder::query()->firstOrFail()->client_address_id)->toBe($address->id)
        ->and(ClientAddress::query()->where('client_id', $client->id)->count())->toBe(1);
});

test('no convierte dos veces', function () {
    $scenario = storeOrderScenario();

    $client = Client::factory()->create(['company_id' => $scenario['company']->id]);
    $customer = StoreCustomer::factory()->linkedTo($client->id)->create(['company_id' => $scenario['company']->id]);
    $order = webOrderFor($scenario, $customer);

    convertWebOrder($scenario, $order)->assertSessionHasNoErrors();
    convertWebOrder($scenario, $order)->assertSessionHasErrors(['status']);

    expect(SalesOrder::query()->count())->toBe(1);
});

test('el detalle sugiere el cliente con el mismo correo', function () {
    $scenario = storeOrderScenario();

    $client = Client::factory()->create(['company_id' => $scenario['company']->id, 'email' => 'ana@example.com']);
    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id, 'email' => 'ana@example.com']);
    $order = webOrderFor($scenario, $customer);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->get(route('store-orders.show', ['company' => $scenario['company']->id, 'id' => $order->id]))
        ->assertInertia(fn ($page) => $page
            ->component('store/orders/show')
            ->where('suggested_client.value', $client->id)
            ->has('store_order.lines', 1));
});
