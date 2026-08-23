<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\Supplier\Models\Supplier;

use function Pest\Laravel\actingAs;

test('a lot can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $lot = ItemLot::factory()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'lot_number' => 'L-OLD',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-lots.update', ['company' => $company->id, 'id' => $lot->id]), [
            'lot_number' => 'L-NEW',
            'manufactured_at' => '2026-02-01',
            'expires_at' => '2026-12-31',
            'supplier_id' => $supplier->id,
        ]);

    $response->assertRedirect(route('item-lots.show', ['company' => $company->id, 'id' => $lot->id]));
    $response->assertSessionHasNoErrors();

    $lot->refresh();
    expect($lot->lot_number)->toBe('L-NEW');
    expect($lot->expires_at->format('Y-m-d'))->toBe('2026-12-31');
    expect($lot->supplier_id)->toBe($supplier->id);
    /** El artículo del lote no se cambia desde el formulario. */
    expect($lot->item_id)->toBe($item->id);
});

test('the update keeps the lot number unique within the item', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);
    ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id, 'lot_number' => 'L-TAKEN']);
    $lot = ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id, 'lot_number' => 'L-MINE']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-lots.update', ['company' => $company->id, 'id' => $lot->id]), [
            'lot_number' => 'L-TAKEN',
        ])
        ->assertSessionHasErrors('lot_number');
});

test('a lot keeps its own lot number on update', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);
    $lot = ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id, 'lot_number' => 'L-SAME']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-lots.update', ['company' => $company->id, 'id' => $lot->id]), [
            'lot_number' => 'L-SAME',
            'expires_at' => '2028-01-01',
        ])
        ->assertSessionHasNoErrors();

    expect($lot->refresh()->expires_at->format('Y-m-d'))->toBe('2028-01-01');
});
