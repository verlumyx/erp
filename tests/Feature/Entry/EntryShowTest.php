<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

/** Los props que Inertia le pasa a la pantalla de detalle. */
function entryShowProps(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Entry\Models\Entry $entry,
): array {
    return actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('entries.show', ['company' => $company->id, 'id' => $entry->id]))
        ->assertOk()
        ->viewData('page')['props'];
}

test('the lots and serials of a line travel as lists, not wrapped in data', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    \App\Modules\Item\Models\Item::where('id', $item->id)->update(['type' => 'serialized']);

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'location_id' => $location->id,
            'lots' => [['lot_number' => 'L-A', 'quantity' => 2]],
            'serials' => [
                ['serial_number' => 'S-1', 'lot_number' => 'L-A'],
                ['serial_number' => 'S-2', 'lot_number' => 'L-A'],
            ],
        ]],
    ]);

    $line = entryShowProps($user, $company, $entry)['entry']['lines'][0];

    /**
     * Una colección de recursos sin resolver se serializa como `{data: [...]}`,
     * y la pantalla la recorre con `.filter()`: tiene que ser una lista.
     */
    expect(array_is_list($line['lots']))->toBeTrue();
    expect(array_is_list($line['serials']))->toBeTrue();

    expect($line['lots'])->toHaveCount(1);
    expect($line['lots'][0]['lot_number'])->toBe('L-A');
    expect($line['serials'])->toHaveCount(2);
    expect($line['serials'][0]['serial_number'])->toBe('S-1');
});

test('a line without traceability carries empty lists', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 5,
            'location_id' => $location->id,
        ]],
    ]);

    $line = entryShowProps($user, $company, $entry)['entry']['lines'][0];

    expect($line['lots'])->toBe([]);
    expect($line['serials'])->toBe([]);
});
