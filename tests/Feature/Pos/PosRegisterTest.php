<?php

declare(strict_types=1);

use App\Modules\Pos\Models\PosRegister;

use function Pest\Laravel\actingAs;

test('a pos register can be created through HTTP', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

    $payload = [
        'name' => 'Caja Principal 01',
        'warehouse_id' => $warehouse->id,
        'default_client_id' => $client->id,
        'invoice_series' => 'CAJ01',
        'receipt_printer_name' => 'EPSON TM-T20III',
        'allow_price_override' => 'no',
        'allow_custom_discounts' => 'yes',
        'max_discount_percent' => 10,
        'notes' => 'Ubicada en pasillo central',
    ];

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('pos-registers.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('pos-registers.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $register = PosRegister::where('company_id', $company->id)->first();
    expect($register)->not->toBeNull();
    expect($register->code)->toBe('CAJ000001');
    expect($register->name)->toBe('Caja Principal 01');
    expect($register->warehouse_id)->toBe($warehouse->id);
    expect($register->default_client_id)->toBe($client->id);
    expect($register->allow_custom_discounts)->toBe('yes');
    expect((float) $register->max_discount_percent)->toBe(10.0);
    expect($register->status)->toBe('active');
});

test('pos registers generate sequential CAJ codes per company', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('pos-registers.store', ['company' => $company->id]), [
            'name' => 'Caja 01',
            'warehouse_id' => $warehouse->id,
            'default_client_id' => $client->id,
        ])
        ->assertSessionHasNoErrors();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('pos-registers.store', ['company' => $company->id]), [
            'name' => 'Caja 02',
            'warehouse_id' => $warehouse->id,
            'default_client_id' => $client->id,
        ])
        ->assertSessionHasNoErrors();

    $registers = PosRegister::where('company_id', $company->id)->orderBy('code')->get();
    expect($registers)->toHaveCount(2);
    expect($registers[0]->code)->toBe('CAJ000001');
    expect($registers[1]->code)->toBe('CAJ000002');
});

test('a pos register can be updated', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

    $register = PosRegister::create([
        'company_id' => $company->id,
        'code' => 'CAJ000001',
        'name' => 'Caja Inicial',
        'warehouse_id' => $warehouse->id,
        'default_client_id' => $client->id,
        'created_by' => $user->id,
        'status' => 'active',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('pos-registers.update', ['company' => $company->id, 'id' => $register->id]), [
            'name' => 'Caja Modificada',
            'warehouse_id' => $warehouse->id,
            'default_client_id' => $client->id,
            'max_discount_percent' => 15,
        ]);

    $response->assertRedirect(route('pos-registers.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $register->refresh();
    expect($register->name)->toBe('Caja Modificada');
    expect((float) $register->max_discount_percent)->toBe(15.0);
});

test('a pos register status can be toggled', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

    $register = PosRegister::create([
        'company_id' => $company->id,
        'code' => 'CAJ000001',
        'name' => 'Caja Activa',
        'warehouse_id' => $warehouse->id,
        'default_client_id' => $client->id,
        'created_by' => $user->id,
        'status' => 'active',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('pos-registers.update-status', ['company' => $company->id, 'id' => $register->id]), [
            'status' => 'inactive',
        ])
        ->assertRedirect(route('pos-registers.index', ['company' => $company->id]));

    $register->refresh();
    expect($register->status)->toBe('inactive');
});

test('available registers can be listed via lookup', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

    PosRegister::create([
        'company_id' => $company->id,
        'code' => 'CAJ000001',
        'name' => 'Caja Activa',
        'warehouse_id' => $warehouse->id,
        'default_client_id' => $client->id,
        'created_by' => $user->id,
        'status' => 'active',
    ]);

    PosRegister::create([
        'company_id' => $company->id,
        'code' => 'CAJ000002',
        'name' => 'Caja Inactiva',
        'warehouse_id' => $warehouse->id,
        'default_client_id' => $client->id,
        'created_by' => $user->id,
        'status' => 'inactive',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('pos-registers.lookup', ['company' => $company->id]));

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['code'])->toBe('CAJ000001');
});

test('create and edit pos register pages load correctly with client tax_number', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

    $register = PosRegister::create([
        'company_id' => $company->id,
        'code' => 'CAJ000001',
        'name' => 'Caja Activa',
        'warehouse_id' => $warehouse->id,
        'default_client_id' => $client->id,
        'created_by' => $user->id,
        'status' => 'active',
    ]);

    $createResponse = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('pos-registers.create', ['company' => $company->id]));

    $createResponse->assertOk();

    $editResponse = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('pos-registers.edit', ['company' => $company->id, 'id' => $register->id]));

    $editResponse->assertOk();
});
