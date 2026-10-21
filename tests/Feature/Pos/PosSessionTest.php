<?php

declare(strict_types=1);

use App\Modules\Pos\Models\PosCashMovement;
use App\Modules\Pos\Models\PosRegister;
use App\Modules\Pos\Models\PosSession;
use App\Modules\User\Models\User;

use function Pest\Laravel\actingAs;

test('a user can open a pos session on an available register', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

    $register = PosRegister::create([
        'company_id' => $company->id,
        'code' => 'CAJ000001',
        'name' => 'Caja Mostrador',
        'warehouse_id' => $warehouse->id,
        'default_client_id' => $client->id,
        'created_by' => $user->id,
        'status' => 'active',
    ]);

    $payload = [
        'pos_register_id' => $register->id,
        'opening_cash_usd' => 150.00,
        'opening_cash_ves' => 2000.00,
        'notes' => 'Apertura normal',
    ];

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('pos-sessions.open.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('pos.terminal', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $terminalResponse = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('pos.terminal', ['company' => $company->id]));

    $terminalResponse->assertOk();

    $session = PosSession::where('company_id', $company->id)->first();
    expect($session)->not->toBeNull();
    expect($session->code)->toBe('COR000001');
    expect($session->user_id)->toBe($user->id);
    expect($session->pos_register_id)->toBe($register->id);
    expect((float) $session->opening_cash_usd)->toBe(150.00);
    expect((float) $session->opening_cash_ves)->toBe(2000.00);
    expect($session->status)->toBe('confirmed');
    expect($session->closed_at)->toBeNull();
});

test('a user cannot open two concurrent sessions in the same company', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

    $reg1 = PosRegister::create([
        'company_id' => $company->id,
        'code' => 'CAJ000001',
        'name' => 'Caja 01',
        'warehouse_id' => $warehouse->id,
        'default_client_id' => $client->id,
        'created_by' => $user->id,
        'status' => 'active',
    ]);

    $reg2 = PosRegister::create([
        'company_id' => $company->id,
        'code' => 'CAJ000002',
        'name' => 'Caja 02',
        'warehouse_id' => $warehouse->id,
        'default_client_id' => $client->id,
        'created_by' => $user->id,
        'status' => 'active',
    ]);

    // First session
    PosSession::create([
        'company_id' => $company->id,
        'code' => 'COR000001',
        'pos_register_id' => $reg1->id,
        'user_id' => $user->id,
        'opened_at' => now(),
        'status' => 'confirmed',
    ]);

    // Attempt second session
    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('pos-sessions.open.store', ['company' => $company->id]), [
            'pos_register_id' => $reg2->id,
            'opening_cash_usd' => 50,
            'opening_cash_ves' => 0,
        ]);

    $response->assertSessionHasErrors(['pos_session']);
    expect(PosSession::count())->toBe(1);
});

test('a register cannot have two active sessions from different users', function () {
    [$user1, $company, $client, $warehouse] = salesOrderScenario();

    $user2 = User::factory()->create();
    $user2->companies()->attach($company->id, [
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'role_id' => $user1->companies()->first()->pivot->role_id,
        'status' => 'active',
    ]);

    $register = PosRegister::create([
        'company_id' => $company->id,
        'code' => 'CAJ000001',
        'name' => 'Caja Única',
        'warehouse_id' => $warehouse->id,
        'default_client_id' => $client->id,
        'created_by' => $user1->id,
        'status' => 'active',
    ]);

    // User 1 opens register
    PosSession::create([
        'company_id' => $company->id,
        'code' => 'COR000001',
        'pos_register_id' => $register->id,
        'user_id' => $user1->id,
        'opened_at' => now(),
        'status' => 'confirmed',
    ]);

    // User 2 attempts to open same register
    $response = actingAs($user2)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('pos-sessions.open.store', ['company' => $company->id]), [
            'pos_register_id' => $register->id,
            'opening_cash_usd' => 20,
            'opening_cash_ves' => 0,
        ]);

    $response->assertSessionHasErrors(['pos_register_id']);
    expect(PosSession::count())->toBe(1);
});

test('a cash movement can be registered in an active session', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

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
        'opening_cash_usd' => 100,
        'status' => 'confirmed',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->postJson(route('pos-sessions.cash-movements.store', ['company' => $company->id, 'id' => $session->id]), [
            'movement_type' => 'out',
            'concept' => 'cash_drop',
            'currency' => 'USD',
            'amount' => 40.00,
            'reason' => 'Retiro de seguridad a caja fuerte',
        ]);

    $response->assertOk();
    $movement = PosCashMovement::where('pos_session_id', $session->id)->first();
    expect($movement)->not->toBeNull();
    expect($movement->movement_type)->toBe('out');
    expect($movement->concept)->toBe('cash_drop');
    expect((float) $movement->amount)->toBe(40.00);
});

test('a session can be closed with blind count declarations and denominations', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

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
        'opened_at' => now()->subHours(8),
        'opening_cash_usd' => 100.00,
        'opening_cash_ves' => 500.00,
        'status' => 'confirmed',
    ]);

    $closePayload = [
        'declared_cash_usd' => 100.00,
        'declared_cash_ves' => 500.00,
        'declared_card_ves' => 0.00,
        'declared_mobile_ves' => 0.00,
        'closing_notes' => 'Cierre sin novedades',
        'denominations' => [
            ['currency' => 'USD', 'denomination' => 50.00, 'count' => 2, 'total_amount' => 100.00],
            ['currency' => 'VES', 'denomination' => 100.00, 'count' => 5, 'total_amount' => 500.00],
        ],
    ];

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('pos-sessions.close', ['company' => $company->id, 'id' => $session->id]), $closePayload);

    $response->assertRedirect(route('pos-sessions.show', ['company' => $company->id, 'id' => $session->id]));
    $response->assertSessionHasNoErrors();

    $session->refresh();
    expect($session->status)->toBe('completed');
    expect($session->closed_at)->not->toBeNull();
    expect((float) $session->declared_cash_usd)->toBe(100.00);
    expect((float) $session->declared_cash_ves)->toBe(500.00);
    expect((float) $session->expected_cash_usd)->toBe(100.00);
    expect((float) $session->difference_cash_usd)->toBe(0.00);
    expect((float) $session->difference_cash_ves)->toBe(0.00);
    expect($session->denominations)->toHaveCount(2);

    // Test PDF generation for closed session
    $pdfResponse = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('pos-sessions.pdf', ['company' => $company->id, 'id' => $session->id]));

    $pdfResponse->assertOk();
    $pdfResponse->assertHeader('content-type', 'application/pdf');
    expect($pdfResponse->getContent())->toStartWith('%PDF-');
});
