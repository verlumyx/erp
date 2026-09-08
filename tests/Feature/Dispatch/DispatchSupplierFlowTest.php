<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Models\PurchaseReturnLine;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Tax\Models\Tax;

use function Pest\Laravel\actingAs;

/**
 * El despacho espejo de una devolución de compra va a un proveedor, no a un
 * cliente. Se genera en borrador al confirmar la devolución y quien prepara el
 * retiro lo abre para elegir ubicación, lote y serie antes de confirmarlo. Esa
 * edición pasa por el mismo endpoint del despacho de venta, así que tiene que
 * aceptar un destinatario que no es un cliente.
 */
function confirmedReturnDispatch(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
): array {
    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $dispatch = Dispatch::with('lines')->where('sourceable_id', $return->id)->firstOrFail();

    return [$return, $dispatch];
}

/**
 * @return array<string, mixed>
 */
function supplierDispatchPayload(
    Dispatch $dispatch,
    PurchaseReturn $return,
    Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    array $overrides = [],
): array {
    $line = $dispatch->lines->first();

    return [
        'recipient_type' => 'supplier',
        'recipient_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'dispatch_date' => now()->toDateString(),
        'sourceable_type' => 'purchase_return',
        'sourceable_id' => $return->id,
        'lines' => [
            [
                'id' => $line->id,
                'item_id' => $line->item_id,
                'measurement_unit_id' => $line->measurement_unit_id,
                'quantity' => (float) $line->quantity,
                'sourceable_type' => 'purchase_return_line',
                'sourceable_id' => $line->sourceable_id,
            ],
        ],
        ...$overrides,
    ];
}

test('a mirror dispatch to a supplier can be edited without a client', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    [$return, $dispatch] = confirmedReturnDispatch($user, $company, $supplier, $warehouse, $item, $unit);

    /** Baja lo que sale de 2 a 1: sigue por debajo de lo devuelto. */
    $payload = supplierDispatchPayload($dispatch, $return, $supplier, $warehouse, [
        'lines' => [
            [
                'id' => $dispatch->lines->first()->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'sourceable_type' => 'purchase_return_line',
                'sourceable_id' => $dispatch->lines->first()->sourceable_id,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('dispatches.update', ['company' => $company->id, 'id' => $dispatch->id]), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dispatches.show', ['company' => $company->id, 'id' => $dispatch->id]));

    $dispatch->refresh()->load('lines');

    /** El destinatario sigue siendo el proveedor: la edición no lo vuelve cliente. */
    expect($dispatch->recipient_type)->toBe(Supplier::MORPH_ALIAS);
    expect($dispatch->recipient_id)->toBe($supplier->id);
    expect($dispatch->sourceable_type)->toBe(PurchaseReturn::MORPH_ALIAS);

    $line = $dispatch->lines->firstWhere('status', 'active');
    expect((float) $line->quantity)->toBe(1.0);
    expect($line->sourceable_type)->toBe(PurchaseReturnLine::MORPH_ALIAS);
    expect($line->sourceable_id)->toBe($return->lines->first()->id);
});

test('the mirror dispatch copies the tax of the return line', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);

    /**
     * La devolución trae su impuesto de la factura de compra; aquí se fija a
     * mano sobre la línea para probar que el espejo lo copia sin depender de
     * toda la cadena de compra.
     */
    $tax = Tax::factory()->create(['company_id' => $company->id]);

    PurchaseReturnLine::where('id', $return->lines->first()->id)->update([
        'unit_price' => 100,
        'tax_id' => $tax->id,
        'tax_percent' => 16,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $line = Dispatch::with('lines')
        ->where('sourceable_id', $return->id)
        ->firstOrFail()
        ->lines
        ->first();

    expect($line->tax_id)->toBe($tax->id);
    expect((float) $line->tax_percent)->toBe(16.0);
    /** 2 × 100 = 200 de base, y 16 % de 200 son 32. */
    expect((float) $line->tax_amount)->toBe(32.0);
});

test('a supplier dispatch without a recipient is rejected', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    [$return, $dispatch] = confirmedReturnDispatch($user, $company, $supplier, $warehouse, $item, $unit);

    $payload = supplierDispatchPayload($dispatch, $return, $supplier, $warehouse, [
        'recipient_id' => '',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('dispatches.update', ['company' => $company->id, 'id' => $dispatch->id]), $payload)
        ->assertSessionHasErrors('recipient_id');
});

test('a supplier from another company is rejected', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    [$return, $dispatch] = confirmedReturnDispatch($user, $company, $supplier, $warehouse, $item, $unit);

    [, $otherCompany] = createUserWithCompany();
    $stranger = Supplier::factory()->create(['company_id' => $otherCompany->id]);

    $payload = supplierDispatchPayload($dispatch, $return, $supplier, $warehouse, [
        'recipient_id' => $stranger->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('dispatches.update', ['company' => $company->id, 'id' => $dispatch->id]), $payload)
        ->assertSessionHasErrors('recipient_id');
});
