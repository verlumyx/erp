<?php

use App\Modules\Company\Models\Company;
use App\Modules\Dispatch\Models\Dispatch;

use function Pest\Laravel\actingAs;

test('dispatch automatically assigns a sequential control number (Art. 13.3, 21)', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch1 = createDispatch($user, $company, $client, $warehouse, $item, $unit);
    $dispatch2 = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    expect($dispatch1->control_number)->not->toBeNull();
    expect($dispatch2->control_number)->not->toBeNull();
    expect($dispatch1->control_number)->not->toBe($dispatch2->control_number);
    expect($dispatch1->control_number)->toMatch('/^00-\d{8}$/');
    expect($dispatch2->control_number)->toMatch('/^00-\d{8}$/');
});

test('dispatch preserves manual control number when provided', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'control_number' => '00-00045210',
    ]);

    expect($dispatch->control_number)->toBe('00-00045210');
});

test('dispatch saves non-sale reason and detail (Art. 20, 21)', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'is_sale' => false,
        'transfer_reason' => 'repair',
        'transfer_reason_detail' => 'Traslado al taller mecánico para mantenimiento preventivo',
    ]);

    expect($dispatch->is_sale)->toBeFalse();
    expect($dispatch->transfer_reason)->toBe('repair');
    expect($dispatch->transfer_reason_label)->toBe('Reparación o mantenimiento');
    expect($dispatch->transfer_reason_detail)->toBe('Traslado al taller mecánico para mantenimiento preventivo');

    $props = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.show', ['company' => $company->id, 'id' => $dispatch->id]))
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['dispatch']['is_sale'])->toBeFalse();
    expect($props['dispatch']['transfer_reason'])->toBe('repair');
    expect($props['dispatch']['transfer_reason_label'])->toBe('Reparación o mantenimiento');
    expect($props['dispatch']['transfer_reason_detail'])->toBe('Traslado al taller mecánico para mantenimiento preventivo');
});

test('delivery note blade view renders legal requirements (Art. 20, 21)', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $company->update([
        'rif' => 'J-12345678-9',
        'fiscal_address' => 'Av. Francisco de Miranda, Edif. Parque Cristal, Piso 8, Caracas',
        'taxpayer_type' => 'ordinary',
    ]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'control_number' => '00-00099999',
        'is_sale' => false,
        'transfer_reason' => 'repair',
        'transfer_reason_detail' => 'Reparación de compresor industrial',
    ]);

    $view = view('pdf.dispatch-delivery-note', [
        'dispatch' => $dispatch,
        'company' => $company,
    ])->render();

    // Requerimientos Art. 20 y 21 PA 00071:
    // 1. Título "Guía de Despacho" / "Orden de Entrega"
    expect($view)->toContain('Guía de Despacho');
    expect($view)->toContain('Orden de Entrega');

    // 2. Leyenda obligatoria "sin derecho a crédito fiscal"
    expect($view)->toContain('SIN DERECHO A CRÉDITO FISCAL');

    // 3. Número de control preimpreso/fiscal
    expect($view)->toContain('00-00099999');

    // 4. Motivo del traslado no comercial y su justificación
    expect($view)->toContain('Reparación o mantenimiento');
    expect($view)->toContain('Reparación de compresor industrial');
    expect($view)->toContain('Traslado que no representa venta');

    // 5. Datos fiscales del emisor
    expect($view)->toContain($company->rif);
    expect($view)->toContain($company->fiscal_address);
    expect($view)->toContain('CONTRIBUYENTE ORDINARIO');
});

test('delivery note pdf stream endpoint generates successfully with fiscal elements', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'is_sale' => false,
        'transfer_reason' => 'internal_transfer',
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.delivery-note', ['company' => $company->id, 'id' => $dispatch->id]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))
        ->toContain("nota-entrega-{$dispatch->code}.pdf");
});
