<?php

declare(strict_types=1);

use App\Modules\PurchaseOrder\Commands\PurchaseOrderLineData;

test('it derives the amounts from quantity, price and percentages', function () {
    $line = PurchaseOrderLineData::fromArray([
        'item_id' => 'item-uuid',
        'measurement_unit_id' => 'unit-uuid',
        'quantity' => 10,
        'unit_price' => 100,
        'discount_percent' => 10,
        'tax_percent' => 16,
        'withholding_percent' => 75,
    ]);

    expect($line->discountAmount)->toBe(100.0);
    expect($line->subtotal)->toBe(900.0);
    expect($line->taxAmount)->toBe(144.0);
    /** La retención se practica sobre el impuesto: 75 % de 144. */
    expect($line->withholdingAmount)->toBe(108.0);
    expect($line->total)->toBe(1044.0);
});

test('the discount percentage wins over the amount sent by the client', function () {
    $line = PurchaseOrderLineData::fromArray([
        'item_id' => 'item-uuid',
        'measurement_unit_id' => 'unit-uuid',
        'quantity' => 2,
        'unit_price' => 50,
        'discount_percent' => 25,
        'discount_amount' => 999,
    ]);

    expect($line->discountAmount)->toBe(25.0);
    expect($line->subtotal)->toBe(75.0);
});

test('without a percentage it uses the discount amount that was sent', function () {
    $line = PurchaseOrderLineData::fromArray([
        'item_id' => 'item-uuid',
        'measurement_unit_id' => 'unit-uuid',
        'quantity' => 2,
        'unit_price' => 50,
        'discount_amount' => 10,
    ]);

    expect($line->discountAmount)->toBe(10.0);
    expect($line->subtotal)->toBe(90.0);
    expect($line->total)->toBe(90.0);
});

test('a line with no id is a new row and defaults to active', function () {
    $line = PurchaseOrderLineData::fromArray([
        'item_id' => 'item-uuid',
        'measurement_unit_id' => 'unit-uuid',
        'quantity' => 1,
        'unit_price' => 1,
    ]);

    expect($line->id)->toBeNull();
    expect($line->status)->toBe('active');
});

test('the collection keeps the order in which the lines arrive', function () {
    $lines = PurchaseOrderLineData::collection([
        ['item_id' => 'a', 'measurement_unit_id' => 'u', 'quantity' => 1, 'unit_price' => 1],
        ['item_id' => 'b', 'measurement_unit_id' => 'u', 'quantity' => 2, 'unit_price' => 2],
    ]);

    expect($lines)->toHaveCount(2);
    expect($lines[0]->itemId)->toBe('a');
    expect($lines[1]->itemId)->toBe('b');
});
