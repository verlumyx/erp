<?php

declare(strict_types=1);

use App\Modules\Import\Models\Import;

it('deriva los ítems de las recepciones y reparte el gasto por valor', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);

    $import = createImport($user, $company, $warehouse, $entry);

    expect($import->code)->toBe('IMP000001')
        ->and($import->status)->toBe('draft')
        /** 10 unidades a 25 de landed cost. */
        ->and((float) $import->total_base_value)->toBe(250.0)
        ->and((float) $import->total_charges)->toBe(100.0)
        ->and((float) $import->total_landed_value)->toBe(350.0);

    $line = $import->lines->first();

    expect($import->lines)->toHaveCount(1)
        ->and($line->entry_line_id)->toBe($entry->lines->first()->id)
        ->and((float) $line->base_quantity)->toBe(10.0)
        ->and((float) $line->remaining_quantity)->toBe(10.0)
        ->and((float) $line->allocated_amount)->toBe(100.0)
        /** 100 de flete entre 10 unidades: cada una sube 10. */
        ->and((float) $line->unit_delta)->toBe(10.0)
        ->and((float) $line->capitalized_amount)->toBe(100.0)
        ->and((float) $line->variance_amount)->toBe(0.0);
});

it('congela la tasa del catálogo sin que el formulario la mande', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);

    $import = createImport($user, $company, $warehouse, $entry);

    expect((float) $import->exchange_rate)->toBe(36.5)
        ->and($import->base_currency)->toBe('USD')
        ->and((float) $import->base_exchange_rate)->toBe(36.5);
});

it('honra la tasa escrita a mano cuando la empresa lo permite', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);

    $import = createImport($user, $company, $warehouse, $entry, ['exchange_rate' => 99]);

    expect((float) $import->exchange_rate)->toBe(99.0);
});

it('convierte cada cobro a la moneda del expediente', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    todayExchangeRate($company, $user, 'EUR', 73);

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);

    $import = createImport($user, $company, $warehouse, $entry, [
        'costs' => [
            ['concept' => 'freight', 'currency' => 'USD', 'amount' => 100],
            ['concept' => 'customs', 'currency' => 'EUR', 'amount' => 40],
        ],
    ]);

    /** 40 EUR a 73 bolívares son 2920, y 2920 a 36,5 son 80 dólares. */
    expect((float) $import->costs->firstWhere('concept', 'customs')->converted_amount)->toBe(80.0)
        ->and((float) $import->total_charges)->toBe(180.0);
});

it('deja fuera del reparto lo que no lleva existencia', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $service = \App\Modules\Item\Models\Item::factory()->create([
        'company_id' => $company->id,
        'type' => 'service',
    ]);

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $service->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 10],
            ['item_id' => $service->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1],
        ],
    ]);

    $import = createImport($user, $company, $warehouse, $entry);

    expect($import->lines)->toHaveCount(1)
        ->and($import->lines->first()->item_id)->toBe($item->id);
});

it('solo absorbe gasto lo que la inspección aceptó', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'inspection_status' => 'partial',
        'inspected_by' => $user->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'rejected_quantity' => 2,
            'rejection_reason' => 'Dos cajas llegaron mojadas.',
        ]],
    ]);

    $import = createImport($user, $company, $warehouse, $entry);

    expect((float) $import->lines->first()->base_quantity)->toBe(8.0)
        ->and((float) $import->lines->first()->unit_delta)->toBe(12.5);
});

it('rechaza una entrada que todavía está en borrador', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    $this->actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(
            route('imports.store', ['company' => $company->id]),
            importPayload($warehouse, $entry),
        )
        ->assertSessionHasErrors('entries.0.entry_id');

    expect(Import::count())->toBe(0);
});

it('rechaza una entrada que llegó a otra bodega', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    [$other] = warehouseWithDefaultLocation($company, $user);

    $entry = costableEntry($user, $company, $supplier, $other, $item, $unit);

    $this->actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(
            route('imports.store', ['company' => $company->id]),
            importPayload($warehouse, $entry),
        )
        ->assertSessionHasErrors('entries.0.entry_id');
});

it('no deja costear la misma entrada en dos expedientes vivos', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);

    createImport($user, $company, $warehouse, $entry);

    $this->actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(
            route('imports.store', ['company' => $company->id]),
            importPayload($warehouse, $entry),
        )
        ->assertSessionHasErrors('entries.0.entry_id');
});

it('exige decir cuál cuando el concepto es otro', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);

    $this->actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(
            route('imports.store', ['company' => $company->id]),
            importPayload($warehouse, $entry, [
                'costs' => [['concept' => 'other', 'currency' => 'USD', 'amount' => 10]],
            ]),
        )
        ->assertSessionHasErrors('costs.0.description');
});

it('bloquea el reparto por peso cuando el artículo no lo trae', function (): void {
    [$user, $company, $supplier, $warehouse, $item, $unit] = importScenario();

    $entry = costableEntry($user, $company, $supplier, $warehouse, $item, $unit);

    $this->actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(
            route('imports.store', ['company' => $company->id]),
            importPayload($warehouse, $entry, ['allocation_method' => 'weight']),
        )
        ->assertSessionHasErrors('allocation_method');
});
