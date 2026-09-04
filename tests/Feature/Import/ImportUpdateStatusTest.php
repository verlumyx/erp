<?php

declare(strict_types=1);

use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Import\Models\Import;

it('genera un ajuste de revaluación en borrador al confirmarse', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);
    $import = createImport($user, $company, $warehouse, $entry);

    moveImportTo($user, $company, $import, 'confirmed')->assertSessionHasNoErrors();

    $adjustment = importAdjustment($import);

    expect($import->refresh()->status)->toBe('confirmed')
        ->and($adjustment)->not->toBeNull()
        ->and($adjustment->type)->toBe(Adjustment::REVALUATION_TYPE)
        ->and($adjustment->status)->toBe('draft')
        ->and($adjustment->warehouse_id)->toBe($warehouse->id)
        ->and($adjustment->lines)->toHaveCount(1);

    $line = $adjustment->lines->first();

    /**
     * El costo nuevo es el promedio vigente más el incremento por unidad: 25
     * de compra más 10 de flete repartido entre las diez unidades.
     */
    expect((float) $line->unit_cost)->toBe(35.0)
        ->and((float) $line->counted_quantity)->toBe(10.0)
        /** Una revaluación no mueve cantidad: solo cambia lo que vale la que ya está. */
        ->and((float) $line->base_quantity)->toBe(0.0)
        /** La entrada no fijó ubicación, así que el ajuste tampoco: la pone la bodega. */
        ->and($line->location_id)->toBeNull();
});

it('el expediente no toca el kardex: lo toca el ajuste', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);
    $import = createImport($user, $company, $warehouse, $entry);

    $before = \App\Modules\InventoryMovement\Models\InventoryMovement::count();

    moveImportTo($user, $company, $import, 'confirmed')->assertSessionHasNoErrors();

    expect(\App\Modules\InventoryMovement\Models\InventoryMovement::count())->toBe($before);
});

it('cierra el expediente cuando su ajuste se confirma', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);
    $import = createImport($user, $company, $warehouse, $entry);

    moveImportTo($user, $company, $import, 'confirmed')->assertSessionHasNoErrors();

    applyAdjustment($user, $company, importAdjustment($import))->assertSessionHasNoErrors();

    expect($import->refresh()->status)->toBe('completed')
        /** 10 unidades a 25 valían 250; revaluadas a 35 valen 350. */
        ->and((float) stockAt($item, $location)->total_value)->toBe(350.0)
        ->and((float) stockAt($item, $location)->average_cost)->toBe(35.0);
});

it('reabre el expediente cuando se anula el ajuste que lo cerró', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);
    $import = createImport($user, $company, $warehouse, $entry);

    moveImportTo($user, $company, $import, 'confirmed')->assertSessionHasNoErrors();
    applyAdjustment($user, $company, importAdjustment($import))->assertSessionHasNoErrors();

    moveAdjustmentTo($user, $company, importAdjustment($import), 'cancelled', 'Se costeó de más.')
        ->assertSessionHasNoErrors();

    expect($import->refresh()->status)->toBe('confirmed');
});

it('no se anula mientras su ajuste siga aplicado', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);
    $import = createImport($user, $company, $warehouse, $entry);

    moveImportTo($user, $company, $import, 'confirmed')->assertSessionHasNoErrors();
    applyAdjustment($user, $company, importAdjustment($import))->assertSessionHasNoErrors();

    moveImportTo($user, $company, $import->refresh(), 'cancelled', 'Llegó otra factura.')
        ->assertSessionHasErrors('status');

    expect($import->refresh()->status)->toBe('completed');
});

it('anula con él el ajuste que todavía estaba en borrador', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);
    $import = createImport($user, $company, $warehouse, $entry);

    moveImportTo($user, $company, $import, 'confirmed')->assertSessionHasNoErrors();

    $adjustmentId = $import->refresh()->adjustment_id;

    moveImportTo($user, $company, $import, 'cancelled', 'El embarque llegó partido.')
        ->assertSessionHasNoErrors();

    expect($import->refresh()->status)->toBe('cancelled')
        ->and($import->adjustment_id)->toBeNull()
        ->and(Adjustment::find($adjustmentId)->status)->toBe('cancelled');
});

it('libera la entrada cuando el expediente se anula', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);
    $import = createImport($user, $company, $warehouse, $entry);

    moveImportTo($user, $company, $import, 'cancelled', 'Se rehace con todo el gasto.')
        ->assertSessionHasNoErrors();

    $second = createImport($user, $company, $warehouse, $entry);

    expect($second->lines)->toHaveCount(1);
});

it('no confirma un expediente sin gasto que capitalizar', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);

    $import = createImport($user, $company, $warehouse, $entry, [
        'costs' => [['concept' => 'freight', 'currency' => 'USD', 'amount' => 0]],
    ]);

    moveImportTo($user, $company, $import, 'confirmed')->assertSessionHasErrors('status');

    expect($import->refresh()->status)->toBe('draft')
        ->and($import->adjustment_id)->toBeNull();
});

it('no se edita una vez confirmado', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);
    $import = createImport($user, $company, $warehouse, $entry);

    moveImportTo($user, $company, $import, 'confirmed')->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('imports.update', ['company' => $company->id, 'id' => $import->id]),
            importPayload($warehouse, $entry, ['reference' => 'BL-9999']),
        )
        ->assertSessionHasErrors('status');

    expect($import->refresh()->reference)->toBe('BL-0001');
});

it('exige un motivo para anular', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);
    $import = createImport($user, $company, $warehouse, $entry);

    moveImportTo($user, $company, $import, 'cancelled')
        ->assertSessionHasErrors('cancellation_reason');

    expect($import->refresh()->status)->toBe(Import::STATUSES[0]);
});
