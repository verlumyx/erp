<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;

use function Pest\Laravel\actingAs;

test('a draft order can be confirmed and records who approved it', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
        'status' => 'draft',
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-orders.update-status', ['company' => $company->id, 'id' => $order->id]), [
            'status' => 'confirmed',
        ]);

    $response->assertRedirect(route('purchase-orders.show', ['company' => $company->id, 'id' => $order->id]));
    $response->assertSessionHasNoErrors();

    $order->refresh();
    expect($order->status)->toBe('confirmed');
    expect($order->approved_by)->toBe($user->id);
    expect($order->approved_at)->not->toBeNull();
});

/**
 * Confirmar valora la orden con la tasa del día, y si esa tasa no está cargada
 * el resolver la frena. El mensaje debe llegar como flash `error` —el que
 * alimenta el toast— además de la clave `exchange_rate`, porque la pantalla de
 * ver no lee esa clave: sin el flash el usuario solo vería el fallo abriendo el
 * inspector del navegador.
 */
test('confirming without a loaded rate flashes the error so the toast shows it', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    /** Se cae la tasa del día: la del catálogo deja de estar disponible. */
    ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['status' => 'inactive']);

    /** El resolver cachea por request; en producción cada petición estrena caché. */
    app()->forgetScopedInstances();

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-orders.update-status', ['company' => $company->id, 'id' => $order->id]), [
            'status' => 'confirmed',
        ]);

    $response->assertSessionHasErrors('exchange_rate');
    $response->assertSessionHas('error', 'No hay tasa de cambio cargada para USD al '.now()->toDateString().'.');

    expect($order->refresh()->status)->toBe('draft');
});

test('cancelling requires a reason and stamps the cancellation', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
        'status' => 'draft',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-orders.update-status', ['company' => $company->id, 'id' => $order->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasErrors('cancellation_reason');

    expect($order->refresh()->status)->toBe('draft');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-orders.update-status', ['company' => $company->id, 'id' => $order->id]), [
            'status' => 'cancelled',
            'cancellation_reason' => 'El proveedor no tiene existencia.',
        ])
        ->assertSessionHasNoErrors();

    $order->refresh();
    expect($order->status)->toBe('cancelled');
    expect($order->cancellation_reason)->toBe('El proveedor no tiene existencia.');
    expect($order->cancelled_at)->not->toBeNull();
});

test('the order cannot skip straight from draft to completed', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
        'status' => 'draft',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-orders.update-status', ['company' => $company->id, 'id' => $order->id]), [
            'status' => 'completed',
        ])
        ->assertSessionHasErrors('status');

    expect($order->refresh()->status)->toBe('draft');
});

test('a cancelled order is a terminal state', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->cancelled()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-orders.update-status', ['company' => $company->id, 'id' => $order->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasErrors('status');

    expect($order->refresh()->status)->toBe('cancelled');
});

test('a confirmed order cannot be declared partial or completed by hand', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    /**
     * El avance lo escriben la Entrada y la Factura de compra al confirmarse;
     * por esta ruta solo pasan las decisiones del usuario.
     * Ver `PurchaseOrderFulfillmentStatusTest`.
     */
    foreach (['partial', 'completed'] as $status) {
        actingAs($user)->withSession(['current_company_id' => $company->id])
            ->put(route('purchase-orders.update-status', ['company' => $company->id, 'id' => $order->id]), [
                'status' => $status,
            ])
            ->assertSessionHasErrors('status');

        expect($order->refresh()->status)->toBe('confirmed');
    }
});

test('a user without permission cannot change the status', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    assignRoleWithPermissions($user, $company, ['purchase-orders.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-orders.update-status', ['company' => $company->id, 'id' => $order->id]), [
            'status' => 'confirmed',
        ])
        ->assertForbidden();
});
