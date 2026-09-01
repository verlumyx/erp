<?php

declare(strict_types=1);

use App\Modules\Entry\Models\Entry;
use App\Modules\Supplier\Models\Supplier;

use function Pest\Laravel\actingAs;

test('the lookup only offers entries that no purchase invoice backs yet', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    /** Un borrador todavía no metió mercancía: no hay nada que respaldar. */
    createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    $posted = createEntry($user, $company, $supplier, $warehouse, $item, $unit);
    moveEntryTo($user, $company, $posted, 'confirmed')->assertSessionHasNoErrors();

    $invoiced = createEntry($user, $company, $supplier, $warehouse, $item, $unit);
    moveEntryTo($user, $company, $invoiced, 'confirmed')->assertSessionHasNoErrors();
    Entry::where('id', $invoiced->id)->update(['is_invoiced' => 'yes']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('entries.lookup', ['company' => $company->id]));

    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['value'])->toBe($posted->id);
    expect($data[0]['meta']['supplier_id'])->toBe($supplier->id);
    expect($data[0]['meta']['supplier_name'])->toBe($supplier->name);
    expect($data[0]['meta']['is_invoiced'])->toBe('no');
});

test('hydrating an already chosen entry ignores the state filter', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit);
    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();
    Entry::where('id', $entry->id)->update(['is_invoiced' => 'yes']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('entries.lookup', ['company' => $company->id, 'ids' => $entry->id]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

test('the lookup filters by supplier and by free text', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);

    $mine = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'supplier_document' => 'REM-ABC',
    ]);
    moveEntryTo($user, $company, $mine, 'confirmed')->assertSessionHasNoErrors();

    $theirs = createEntry($user, $company, $other, $warehouse, $item, $unit, [
        'supplier_id' => $other->id,
    ]);
    moveEntryTo($user, $company, $theirs, 'confirmed')->assertSessionHasNoErrors();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('entries.lookup', ['company' => $company->id, 'supplier_id' => $other->id]));

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($theirs->id);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('entries.lookup', ['company' => $company->id, 'q' => 'REM-ABC']));

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($mine->id);
});

test('the lookup needs the permission', function () {
    [$user, $company] = entryScenario();

    assignRoleWithPermissions($user, $company, []);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('entries.lookup', ['company' => $company->id]))
        ->assertForbidden();
});

test('the purchase order lookup carries the lines an entry receives', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-orders.update-status', ['company' => $company->id, 'id' => $order->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-orders.lookup', ['company' => $company->id]));

    $response->assertOk();

    $lines = $response->json('data.0.meta.lines');
    expect($lines)->toHaveCount(1);
    expect($lines[0]['id'])->toBe($order->lines->first()->id);
    expect($lines[0]['item_id'])->toBe($item->id);
    expect($lines[0]['measurement_unit_id'])->toBe($unit->id);
    expect((float) $lines[0]['quantity'])->toBe(10.0);
    expect((float) $lines[0]['received_quantity'])->toBe(0.0);
});
