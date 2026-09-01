<?php

declare(strict_types=1);

use App\Modules\Entry\Models\Entry;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\Tax\Models\Tax;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('an entry can be created', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'supplier_document' => 'REM-0099',
        'carrier' => 'Transporte Zulia',
        'tracking_number' => 'TRK-77',
        'notes' => 'Llegó completa.',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('entries.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $entry = Entry::with('lines')->find($payload['id']);
    expect($entry)->not->toBeNull();
    expect($entry->code)->toBe('ENT000001');
    expect($entry->status)->toBe('draft');
    expect($entry->company_id)->toBe($company->id);
    expect($entry->created_by)->toBe($user->id);
    expect($entry->supplier_id)->toBe($supplier->id);
    expect($entry->warehouse_id)->toBe($warehouse->id);
    expect($entry->entry_type)->toBe('purchase');
    expect($entry->inspection_status)->toBe('pending');
    expect($entry->supplier_document)->toBe('REM-0099');
    /** La factura de compra llega después: la entrada nace sin respaldar. */
    expect($entry->is_invoiced)->toBe('no');
    expect($entry->lines)->toHaveCount(1);

    $line = $entry->lines->first();
    expect($line->line_number)->toBe(1);
    expect($line->company_id)->toBe($company->id);
    expect((float) $line->quantity)->toBe(10.0);
    expect((float) $line->base_quantity)->toBe(10.0);
    /** Sin rechazo, lo aceptado es todo lo que llegó. */
    expect((float) $line->received_quantity)->toBe(10.0);
    expect((float) $line->rejected_quantity)->toBe(0.0);
    expect((float) $line->subtotal)->toBe(250.0);
    expect((float) $line->unit_cost)->toBe(25.0);
    expect((float) $line->landed_cost)->toBe(25.0);

    expect((float) $entry->total_quantity)->toBe(10.0);
    expect((float) $entry->total_cost)->toBe(250.0);
});

test('the amounts are calculated on the backend and ignore what the client sends', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $tax = Tax::factory()->create(['company_id' => $company->id, 'percentage' => 16]);

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 4,
            'unit_price' => 100,
            'discount_percent' => 10,
            'tax_id' => $tax->id,
            'tax_percent' => 16,
            /** Mentiras del cliente: el backend las recalcula. */
            'subtotal' => 1,
            'tax_amount' => 1,
            'total' => 1,
        ]],
    ]);

    $line = $entry->lines->first();
    expect((float) $line->discount_amount)->toBe(40.0);
    expect((float) $line->subtotal)->toBe(360.0);
    expect((float) $line->tax_amount)->toBe(57.6);
    expect((float) $line->total)->toBe(417.6);
    /** El costo del kardex es el neto de descuento, sin impuesto. */
    expect((float) $line->unit_cost)->toBe(90.0);
});

test('the accepted quantity is what arrived minus what inspection rejected', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'inspection_status' => 'partial',
        'inspected_by' => $user->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'rejected_quantity' => 3,
            'rejection_reason' => 'Tres cajas mojadas.',
            'unit_price' => 25,
        ]],
    ]);

    $line = $entry->lines->first();
    expect((float) $line->received_quantity)->toBe(7.0);
    expect((float) $line->rejected_quantity)->toBe(3.0);
    /** Lo rechazado no entró: el total de la cabecera solo cuenta lo aceptado. */
    expect((float) $entry->total_quantity)->toBe(7.0);
    expect((float) $entry->total_cost)->toBe(175.0);
});

test('the freight and other charges are prorated into the landed cost by line value', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $other = Item::factory()->create(['company_id' => $company->id, 'is_purchasable' => 'yes']);
    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $other->id,
        'measurement_unit_id' => $unit->id,
    ]);

    /** Dos líneas: una vale 300 y la otra 100, así que cargan 3 a 1. */
    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'freight_amount' => 30,
        'other_charges' => 10,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 30,
            ],
            [
                'item_id' => $other->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 10,
            ],
        ],
    ]);

    $lines = $entry->lines->sortBy('line_number')->values();

    /** 40 de gastos sobre 400 de mercancía: un 10 % a cada costo. */
    expect((float) $lines[0]->unit_cost)->toBe(30.0);
    expect((float) $lines[0]->landed_cost)->toBe(33.0);
    expect((float) $lines[1]->unit_cost)->toBe(10.0);
    expect((float) $lines[1]->landed_cost)->toBe(11.0);

    /** Y el valor ingresado es la mercancía más los gastos, sin perder un centavo. */
    expect((float) $entry->total_cost)->toBe(440.0);
});

test('the cost is expressed per base unit when the line uses another unit', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'conversion_factor' => 12,
        'is_base' => 'no',
    ]);

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $box->id,
            'quantity' => 5,
            'unit_price' => 120,
        ]],
    ]);

    $line = $entry->lines->first();
    expect((float) $line->quantity)->toBe(5.0);
    expect((float) $line->base_quantity)->toBe(60.0);
    /** 600 entre 60 unidades base: la caja de 120 son 10 por unidad. */
    expect((float) $line->unit_cost)->toBe(10.0);
    expect((float) $entry->total_quantity)->toBe(60.0);
});

test('an entry needs at least one active line', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 10,
            'status' => 'inactive',
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines');

    expect(Entry::count())->toBe(0);
});

test('a purchase entry needs a supplier', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $payload = entryPayload($supplier, $warehouse, $item, $unit, ['supplier_id' => null]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('supplier_id');
});

test('an initial inventory entry has neither supplier nor source document', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $payload = entryPayload($supplier, $warehouse, $item, $unit, ['entry_type' => 'initial']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('supplier_id');
});

test('an item only loads its initial inventory once per warehouse', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'entry_type' => 'initial',
        'supplier_id' => null,
    ]);

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'entry_type' => 'initial',
        'supplier_id' => null,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.item_id');

    expect(Entry::count())->toBe(1);
});

test('what is rejected has to be explained and cannot exceed what arrived', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'rejected_quantity' => 4,
            'unit_price' => 25,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.rejection_reason');

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'rejected_quantity' => 12,
            'rejection_reason' => 'Todo mal.',
            'unit_price' => 25,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.rejected_quantity');
});

test('an approved inspection cannot reject anything and needs an inspector', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'inspection_status' => 'approved',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'rejected_quantity' => 2,
            'rejection_reason' => 'Dos rotas.',
            'unit_price' => 25,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors(['inspection_status', 'inspected_by']);
});

test('the line location has to belong to the warehouse of the entry', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $other = Warehouse::factory()->create(['company_id' => $company->id]);
    $location = WarehouseLocation::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $other->id,
        'created_by' => $user->id,
    ]);

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 10,
            'location_id' => $location->id,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.location_id');
});

test('a line cannot carry a lot for an item that does not track lots', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $service = Item::factory()->create([
        'company_id' => $company->id,
        'type' => 'service',
        'is_purchasable' => 'yes',
    ]);
    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $service->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $service->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 10,
            'lot_number' => 'L-1',
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.lot_number');
});

test('a serialized item needs one serial per accepted unit', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    Item::where('id', $item->id)->update(['type' => 'serialized']);

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 3,
            'unit_price' => 25,
            'serial_numbers' => ['S-1', 'S-2'],
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.serial_numbers');
});

test('the create screen renders with its catalogs', function () {
    [$user, $company] = entryScenario();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('entries.create', ['company' => $company->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('entries/create')
            ->has('options.warehouses')
            ->has('options.locations')
            ->has('options.taxes')
            ->has('options.receivers')
        );
});
