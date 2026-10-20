<?php

declare(strict_types=1);

use App\Modules\Pos\Models\PosCashMovement;
use App\Modules\Pos\Models\PosPayment;
use App\Modules\Pos\Models\PosRegister;
use App\Modules\Pos\Models\PosSale;
use App\Modules\Pos\Models\PosSession;
use App\Modules\Pos\Services\PosSessionCalculationService;

test('it accurately calculates expected cash, totals, and payment methods for a session', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

    $register = PosRegister::create([
        'company_id' => $company->id,
        'code' => 'CAJ000001',
        'name' => 'Caja Test',
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
        'opening_cash_ves' => 1000.00,
        'status' => 'confirmed',
    ]);

    // 1. Sale 1: Total $20. Paid $20 in cash USD.
    $sale1 = PosSale::create([
        'company_id' => $company->id,
        'code' => 'POS000001',
        'pos_session_id' => $session->id,
        'pos_register_id' => $register->id,
        'client_id' => $client->id,
        'sale_date' => now(),
        'currency' => 'USD',
        'exchange_rate' => 50.0,
        'subtotal' => 20.00,
        'discount_amount' => 0.00,
        'tax_amount' => 0.00,
        'total' => 20.00,
        'total_in_local_currency' => 1000.00,
        'paid_amount' => 20.00,
        'change_amount_usd' => 0.00,
        'change_amount_ves' => 0.00,
        'created_by' => $user->id,
        'status' => 'completed',
    ]);

    PosPayment::create([
        'company_id' => $company->id,
        'pos_sale_id' => $sale1->id,
        'pos_session_id' => $session->id,
        'payment_method' => 'cash',
        'currency' => 'USD',
        'exchange_rate' => 1.0,
        'amount_tendered' => 20.00,
        'amount_applied' => 20.00,
        'amount_in_document_currency' => 20.00,
        'status' => 'active',
    ]);

    // 2. Sale 2: Total $50. Paid with a $100 bill, change $50 USD.
    $sale2 = PosSale::create([
        'company_id' => $company->id,
        'code' => 'POS000002',
        'pos_session_id' => $session->id,
        'pos_register_id' => $register->id,
        'client_id' => $client->id,
        'sale_date' => now(),
        'currency' => 'USD',
        'exchange_rate' => 50.0,
        'subtotal' => 50.00,
        'discount_amount' => 5.00,
        'tax_amount' => 5.00,
        'total' => 50.00,
        'total_in_local_currency' => 2500.00,
        'paid_amount' => 100.00,
        'change_amount_usd' => 50.00,
        'change_amount_ves' => 0.00,
        'created_by' => $user->id,
        'status' => 'completed',
    ]);

    PosPayment::create([
        'company_id' => $company->id,
        'pos_sale_id' => $sale2->id,
        'pos_session_id' => $session->id,
        'payment_method' => 'cash',
        'currency' => 'USD',
        'exchange_rate' => 1.0,
        'amount_tendered' => 100.00,
        'amount_applied' => 50.00,
        'amount_in_document_currency' => 50.00,
        'status' => 'active',
    ]);

    // 3. Sale 3: Total $10. Paid in VES card debit (Bs. 500).
    $sale3 = PosSale::create([
        'company_id' => $company->id,
        'code' => 'POS000003',
        'pos_session_id' => $session->id,
        'pos_register_id' => $register->id,
        'client_id' => $client->id,
        'sale_date' => now(),
        'currency' => 'USD',
        'exchange_rate' => 50.0,
        'subtotal' => 10.00,
        'discount_amount' => 0.00,
        'tax_amount' => 0.00,
        'total' => 10.00,
        'total_in_local_currency' => 500.00,
        'paid_amount' => 10.00,
        'change_amount_usd' => 0.00,
        'change_amount_ves' => 0.00,
        'created_by' => $user->id,
        'status' => 'completed',
    ]);

    PosPayment::create([
        'company_id' => $company->id,
        'pos_sale_id' => $sale3->id,
        'pos_session_id' => $session->id,
        'payment_method' => 'card_debit',
        'currency' => 'VES',
        'exchange_rate' => 50.0,
        'amount_tendered' => 500.00,
        'amount_applied' => 500.00,
        'amount_in_document_currency' => 10.00,
        'status' => 'active',
    ]);

    // 4. Cash movement: Entrada $30 USD (change_replenishment)
    PosCashMovement::create([
        'company_id' => $company->id,
        'pos_session_id' => $session->id,
        'movement_type' => 'in',
        'concept' => 'change_replenishment',
        'currency' => 'USD',
        'exchange_rate' => 1.0,
        'amount' => 30.00,
        'reason' => 'Recarga de billetes pequeños',
        'status' => 'active',
    ]);

    // 5. Cash movement: Salida $50 USD (cash_drop / sangría)
    PosCashMovement::create([
        'company_id' => $company->id,
        'pos_session_id' => $session->id,
        'movement_type' => 'out',
        'concept' => 'cash_drop',
        'currency' => 'USD',
        'exchange_rate' => 1.0,
        'amount' => 50.00,
        'reason' => 'Retiro de seguridad',
        'status' => 'active',
    ]);

    $calcService = app(PosSessionCalculationService::class);
    $result = $calcService->calculate($session);

    // Sales Count
    expect($result['salesCount'])->toBe(3);
    expect($result['totalSalesAmount'])->toBe(80.00); // 20 + 50 + 10
    expect($result['totalDiscountsAmount'])->toBe(5.00);
    expect($result['averageTicket'])->toBe(26.67);

    // Cash USD in drawer:
    // Opening: 100
    // Sale 1: +20
    // Sale 2: +100 tendered - 50 change = +50
    // In: +30
    // Out: -50
    // Expected: 100 + 20 + 50 + 30 - 50 = 150.00
    expect($result['salesCashUsd'])->toBe(70.00); // 20 + 50
    expect($result['cashInUsd'])->toBe(30.00);
    expect($result['cashOutUsd'])->toBe(50.00);
    expect($result['cashUsd'])->toBe(150.00);

    // Card VES
    expect($result['salesCardVes'])->toBe(500.00);
});
