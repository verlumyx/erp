<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\Pos\Models\PosRegister;
use App\Modules\Pos\Models\PosSale;
use App\Modules\Pos\Models\PosSession;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;

use function Pest\Laravel\actingAs;

test('atomic checkout creates sales invoice, dispatch, collection, and completed pos sale', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $warehouse->update(['uses_locations' => 'yes']);

    $location = WarehouseLocation::factory()->default()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    // 1. Initial stock for item in warehouse: 20 units
    ItemStock::factory()->withBalance(20, 5)->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'warehouse_id' => $warehouse->id,
        'location_id' => $location->id,
        'status' => 'active',
    ]);

    // 2. Register & Active Session
    $register = PosRegister::create([
        'company_id' => $company->id,
        'code' => 'CAJ000001',
        'name' => 'Caja Mostrador',
        'warehouse_id' => $warehouse->id,
        'default_client_id' => $client->id,
        'created_by' => $user->id,
        'status' => 'active',
    ]);

    $session = PosSession::create([
        'company_id' => $company->id,
        'code' => 'COR000001',
        'pos_register_id' => $register->id,
        'user_id' => $user->id,
        'opened_at' => now(),
        'opening_cash_usd' => 100.00,
        'opening_cash_ves' => 0.00,
        'status' => 'confirmed',
    ]);

    // 3. Checkout: 2 units of item @ $15 = $30 total. Tendered $50 USD bill, $20 USD change.
    $payload = [
        'pos_session_id' => $session->id,
        'client_id' => $client->id,
        'currency' => 'USD',
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 2.0,
                'unit_price' => 15.0,
                'discount_percent' => 0.0,
                'discount_amount' => 0.0,
                'tax_percent' => 0.0,
                'tax_amount' => 0.0,
                'subtotal' => 30.0,
                'total' => 30.0,
            ],
        ],
        'payments' => [
            [
                'payment_method' => 'cash',
                'currency' => 'USD',
                'exchange_rate' => 1.0,
                'amount_tendered' => 50.0,
                'amount_applied' => 30.0,
                'amount_in_document_currency' => 30.0,
            ],
        ],
        'subtotal' => 30.0,
        'discount_amount' => 0.0,
        'tax_amount' => 0.0,
        'total' => 30.0,
        'total_in_local_currency' => 1500.0,
        'paid_amount' => 50.0,
        'change_amount_usd' => 20.0,
        'change_amount_ves' => 0.0,
    ];

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->postJson(route('pos.checkout', ['company' => $company->id]), $payload);

    $response->assertCreated();
    $data = $response->json();
    expect($data['sale'])->not->toBeNull();
    expect($data['sale']['code'])->toBe('POS000001');
    expect($data['sale']['status'])->toBe('completed');
    expect((float) $data['sale']['total'])->toBe(30.0);
    expect((float) $data['sale']['change_amount_usd'])->toBe(20.0);

    // 4. Verify SalesInvoice was created & confirmed
    $posSale = PosSale::with('lines', 'payments')->find($data['sale']['id']);
    expect($posSale->sales_invoice_id)->not->toBeNull();

    $invoice = SalesInvoice::find($posSale->sales_invoice_id);
    expect($invoice)->not->toBeNull();
    expect($invoice->status)->toBeIn(['confirmed', 'completed']);
    expect((float) $invoice->total)->toBe(30.0);

    // 5. Verify Dispatch was created with sourceable = SalesInvoice
    expect($posSale->dispatch_id)->not->toBeNull();
    $dispatch = Dispatch::find($posSale->dispatch_id);
    expect($dispatch)->not->toBeNull();
    expect($dispatch->status)->toBe('confirmed');
    expect($dispatch->sourceable_type)->toBe(SalesInvoice::MORPH_ALIAS);
    expect($dispatch->sourceable_id)->toBe($invoice->id);

    // 6. Verify inventory stock was reduced: 20 - 2 = 18
    $stock = ItemStock::where('item_id', $item->id)->where('warehouse_id', $warehouse->id)->first();
    expect((float) $stock->quantity)->toBe(18.0);

    // 7. Verify session stats incremented
    $session->refresh();
    expect($session->sales_count)->toBe(1);
    expect((float) $session->total_sales_amount)->toBe(30.0);
});

test('a sale can be put on hold and later discarded', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $register = PosRegister::create([
        'company_id' => $company->id,
        'code' => 'CAJ000001',
        'name' => 'Caja',
        'warehouse_id' => $warehouse->id,
        'default_client_id' => $client->id,
        'created_by' => $user->id,
        'status' => 'active',
    ]);

    $session = PosSession::create([
        'company_id' => $company->id,
        'code' => 'COR000001',
        'pos_register_id' => $register->id,
        'user_id' => $user->id,
        'opened_at' => now(),
        'status' => 'confirmed',
    ]);

    // 1. Put ticket on hold
    $holdResponse = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->postJson(route('pos.hold', ['company' => $company->id]), [
            'pos_session_id' => $session->id,
            'client_id' => $client->id,
            'lines' => [
                [
                    'item_id' => $item->id,
                    'measurement_unit_id' => $unit->id,
                    'quantity' => 1.0,
                    'unit_price' => 10.0,
                    'subtotal' => 10.0,
                    'total' => 10.0,
                ],
            ],
            'notes' => 'Cliente fue al cajero automático',
        ]);

    $holdResponse->assertSuccessful();
    $saleId = $holdResponse->json('sale.id');
    expect($saleId)->not->toBeNull();

    $heldSale = PosSale::find($saleId);
    expect($heldSale->status)->toBe('draft');
    expect($heldSale->lines)->toHaveCount(1);

    // 2. List held sales
    $listResponse = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('pos.held.list', ['company' => $company->id, 'sessionId' => $session->id]));

    $listResponse->assertOk();
    expect($listResponse->json('data'))->toHaveCount(1);

    // 3. Discard held sale
    $discardResponse = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->deleteJson(route('pos.held.discard', ['company' => $company->id, 'id' => $saleId]));

    $discardResponse->assertOk();
    expect(PosSale::find($saleId))->toBeNull();
});

test('a completed pos sale can be cancelled', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $register = PosRegister::create([
        'company_id' => $company->id,
        'code' => 'CAJ000001',
        'name' => 'Caja',
        'warehouse_id' => $warehouse->id,
        'default_client_id' => $client->id,
        'created_by' => $user->id,
        'status' => 'active',
    ]);

    $session = PosSession::create([
        'company_id' => $company->id,
        'code' => 'COR000001',
        'pos_register_id' => $register->id,
        'user_id' => $user->id,
        'opened_at' => now(),
        'status' => 'confirmed',
    ]);

    $sale = PosSale::create([
        'company_id' => $company->id,
        'code' => 'POS000001',
        'pos_session_id' => $session->id,
        'pos_register_id' => $register->id,
        'client_id' => $client->id,
        'sale_date' => now(),
        'currency' => 'USD',
        'exchange_rate' => 1.0,
        'subtotal' => 10.0,
        'total' => 10.0,
        'total_in_local_currency' => 500.0,
        'paid_amount' => 10.0,
        'created_by' => $user->id,
        'status' => 'completed',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->postJson(route('pos.sales.cancel', ['company' => $company->id, 'id' => $sale->id]), [
            'cancellation_reason' => 'Error al marcar producto',
        ]);

    $response->assertOk();

    $sale->refresh();
    expect($sale->status)->toBe('cancelled');
    expect($sale->cancellation_reason)->toBe('Error al marcar producto');
    expect($sale->cancelled_by)->toBe($user->id);

    $session->refresh();
    expect($session->cancelled_sales_count)->toBe(1);
});

test('atomic checkout supports multiple split payment methods including biopago and mobile payment', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $warehouse->update(['uses_locations' => 'yes']);

    $location = WarehouseLocation::factory()->default()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    ItemStock::factory()->withBalance(50, 10)->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'warehouse_id' => $warehouse->id,
        'location_id' => $location->id,
        'status' => 'active',
    ]);

    $register = PosRegister::create([
        'company_id' => $company->id,
        'code' => 'CAJ000001',
        'name' => 'Caja Mostrador',
        'warehouse_id' => $warehouse->id,
        'default_client_id' => $client->id,
        'created_by' => $user->id,
        'status' => 'active',
    ]);

    $session = PosSession::create([
        'company_id' => $company->id,
        'code' => 'COR000001',
        'pos_register_id' => $register->id,
        'user_id' => $user->id,
        'opened_at' => now(),
        'opening_cash_usd' => 100.00,
        'opening_cash_ves' => 0.00,
        'status' => 'confirmed',
    ]);

    // Checkout: 4 units @ $15 = $60 total.
    // Payment 1: $20 USD cash
    // Payment 2: Bs. 1,000 Pago Movil (rate 50 => $20 USD)
    // Payment 3: Bs. 1,000 Biopago (rate 50 => $20 USD)
    $payload = [
        'pos_session_id' => $session->id,
        'client_id' => $client->id,
        'currency' => 'USD',
        'exchange_rate' => 50.0,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 4.0,
                'unit_price' => 15.0,
                'discount_percent' => 0.0,
                'discount_amount' => 0.0,
                'tax_percent' => 0.0,
                'tax_amount' => 0.0,
                'subtotal' => 60.0,
                'total' => 60.0,
            ],
        ],
        'payments' => [
            [
                'payment_method' => 'cash',
                'currency' => 'USD',
                'exchange_rate' => 1.0,
                'amount_tendered' => 20.0,
                'amount_applied' => 20.0,
                'amount_in_document_currency' => 20.0,
            ],
            [
                'payment_method' => 'mobile_payment',
                'currency' => 'VES',
                'exchange_rate' => 50.0,
                'amount_tendered' => 1000.0,
                'amount_applied' => 1000.0,
                'amount_in_document_currency' => 20.0,
                'reference' => '04141234567',
            ],
            [
                'payment_method' => 'biopago',
                'currency' => 'VES',
                'exchange_rate' => 50.0,
                'amount_tendered' => 1000.0,
                'amount_applied' => 1000.0,
                'amount_in_document_currency' => 20.0,
                'reference' => 'APROB-9988',
            ],
        ],
        'subtotal' => 60.0,
        'discount_amount' => 0.0,
        'tax_amount' => 0.0,
        'total' => 60.0,
        'total_in_local_currency' => 3000.0,
        'paid_amount' => 60.0,
        'change_amount_usd' => 0.0,
        'change_amount_ves' => 0.0,
    ];

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->postJson(route('pos.checkout', ['company' => $company->id]), $payload);

    $response->assertCreated();
    $data = $response->json();
    expect($data['sale'])->not->toBeNull();
    expect($data['sale']['status'])->toBe('completed');
    expect((float) $data['sale']['total'])->toBe(60.0);

    $posSale = PosSale::with('payments')->find($data['sale']['id']);
    expect($posSale->payments)->toHaveCount(3);

    $methods = $posSale->payments->pluck('payment_method')->all();
    expect($methods)->toContain('cash');
    expect($methods)->toContain('mobile_payment');
    expect($methods)->toContain('biopago');
});
