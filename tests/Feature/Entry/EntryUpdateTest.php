<?php

declare(strict_types=1);

use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Models\EntryLine;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('a draft entry can be edited', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'supplier_document' => 'REM-123',
        'carrier' => 'Zoom',
        'notes' => 'Corregido tras contar la carga.',
        'lines' => [[
            'id' => $entry->lines->first()->id,
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 6,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('entries.update', ['company' => $company->id, 'id' => $entry->id]), $payload)
        ->assertRedirect(route('entries.show', ['company' => $company->id, 'id' => $entry->id]))
        ->assertSessionHasNoErrors();

    $entry->refresh()->load('lines');
    expect($entry->supplier_document)->toBe('REM-123');
    expect($entry->carrier)->toBe('Zoom');
    expect($entry->lines)->toHaveCount(1);

    $line = $entry->lines->first();
    /** La fila se reconoce por su id y conserva su número de línea. */
    expect($line->line_number)->toBe(1);
    expect((float) $line->quantity)->toBe(6.0);
    /** Seis unidades al promedio de 25 con el que se valora la entrada. */
    expect((float) $line->subtotal)->toBe(150.0);
    expect((float) $entry->total_cost)->toBe(150.0);
});

test('a line that stops coming is deactivated, never deleted', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 2],
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 3],
        ],
    ]);

    $kept = $entry->lines->sortBy('line_number')->first();

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'id' => $kept->id,
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('entries.update', ['company' => $company->id, 'id' => $entry->id]), $payload)
        ->assertSessionHasNoErrors();

    expect(EntryLine::where('entry_id', $entry->id)->count())->toBe(2);
    expect(EntryLine::where('entry_id', $entry->id)->where('status', 'inactive')->count())->toBe(1);

    /** Y los totales solo suman las activas. */
    expect((float) $entry->refresh()->total_cost)->toBe(50.0);
});

test('a lot that stops coming is deactivated, never deleted', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'lots' => [
                ['lot_number' => 'L-A', 'quantity' => 4],
                ['lot_number' => 'L-B', 'quantity' => 6],
            ],
        ]],
    ]);

    $line = $entry->lines->first();
    $kept = $line->lots()->orderBy('line_number')->first();

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'id' => $line->id,
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 4,
            'lots' => [['id' => $kept->id, 'lot_number' => 'L-A', 'quantity' => 4]],
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('entries.update', ['company' => $company->id, 'id' => $entry->id]), $payload)
        ->assertSessionHasNoErrors();

    $lots = $line->lots()->orderBy('line_number')->get();
    expect($lots)->toHaveCount(2);
    expect($lots[0]->status)->toBe('active');
    expect($lots[1]->status)->toBe('inactive');
    /** La fila que se queda conserva su número: el par (línea, número) es único. */
    expect($lots[0]->line_number)->toBe(1);
});

test('a confirmed entry can no longer be edited', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    $payload = entryPayload($supplier, $warehouse, $item, $unit, ['carrier' => 'Otro']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('entries.update', ['company' => $company->id, 'id' => $entry->id]), $payload)
        ->assertSessionHasErrors('status');

    expect($entry->refresh()->carrier)->toBeNull();
});

test('saving the draft again recalculates the cost over the new lines', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    expect((float) $entry->lines->first()->landed_cost)->toBe(25.0);

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'id' => $entry->lines->first()->id,
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 20,
            'unit_price' => 25,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('entries.update', ['company' => $company->id, 'id' => $entry->id]), $payload)
        ->assertSessionHasNoErrors();

    /** El doble de mercancía al mismo precio: el costo por unidad no se mueve. */
    expect((float) $entry->refresh()->lines()->first()->landed_cost)->toBe(25.0);
});

test('the edit screen renders the entry with its catalogs', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('entries.edit', ['company' => $company->id, 'id' => $entry->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('entries/edit')
            ->where('entry.id', $entry->id)
            ->has('entry.lines', 1)
            ->has('options.warehouses')
        );
});

test('an entry of another company is out of reach', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();
    [$otherUser, $otherCompany] = createUserWithCompany();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($otherUser)
        ->withSession(['current_company_id' => $otherCompany->id])
        ->get(route('entries.show', ['company' => $otherCompany->id, 'id' => $entry->id]))
        ->assertNotFound();

    expect(Entry::where('company_id', $otherCompany->id)->count())->toBe(0);
});
