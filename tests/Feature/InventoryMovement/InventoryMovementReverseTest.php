<?php

declare(strict_types=1);

use App\Modules\InventoryMovement\Commands\ReverseInventoryMovementCommand;
use App\Modules\InventoryMovement\Exceptions\InventoryMovementNotFoundException;
use App\Modules\InventoryMovement\Exceptions\MovementAlreadyReversedException;
use App\Modules\InventoryMovement\Exceptions\MovementNotReversibleException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Services\InventoryMovementReverseService;
use App\Modules\ItemStock\Models\ItemStock;
use Illuminate\Support\Str;

/**
 * @param  array<string, mixed>  $overrides
 */
function reverseMovement(string $movementId, array $overrides = []): InventoryMovement
{
    return app(InventoryMovementReverseService::class)->execute(new ReverseInventoryMovementCommand(
        movementId: $movementId,
        movementDate: $overrides['movementDate'] ?? null,
        notes: $overrides['notes'] ?? null,
        createdBy: $overrides['createdBy'] ?? null,
    ));
}

test('reversing an entry emits the opposite movement and gives the stock back', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();

    $entry = registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 5,
    ]);

    $reversal = reverseMovement($entry->id, ['notes' => 'Factura anulada']);

    expect($reversal->type)->toBe('out');
    expect($reversal->reversal_of_id)->toBe($entry->id);
    expect((float) $reversal->quantity)->toBe(10.0);
    expect((float) $reversal->unit_cost)->toBe(5.0);
    expect((float) $reversal->balance_quantity)->toBe(0.0);
    expect($reversal->notes)->toBe('Factura anulada');
    expect((float) ItemStock::first()->quantity)->toBe(0.0);
});

test('the reversed original keeps its numbers and only changes state', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();

    $entry = registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    reverseMovement($entry->id);

    $original = $entry->fresh();

    expect($original->status)->toBe('reversed');
    expect((float) $original->quantity)->toBe(10.0);
    expect((float) $original->balance_quantity)->toBe(10.0);
    expect((float) $original->balance_value)->toBe(50.0);
});

test('reversing an exit loads the stock back', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);
    $exit = registerInventoryMovement($company, $item, $warehouse, $location, ['type' => 'out', 'quantity' => 4]);

    $reversal = reverseMovement($exit->id);

    expect($reversal->type)->toBe('in');
    expect((float) $reversal->balance_quantity)->toBe(10.0);
    expect((float) ItemStock::first()->quantity)->toBe(10.0);
});

test('the reversal copies the origin document of the original', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();

    $originId = (string) Str::uuid7();
    $entry = registerInventoryMovement($company, $item, $warehouse, $location, [
        'originType' => 'purchase_invoice',
        'originId' => $originId,
    ]);

    $reversal = reverseMovement($entry->id);

    expect($reversal->origin_type)->toBe('purchase_invoice');
    expect($reversal->origin_id)->toBe($originId);
});

test('a movement is reversed only once', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();

    $entry = registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    reverseMovement($entry->id);
    reverseMovement($entry->id);
})->throws(MovementAlreadyReversedException::class);

test('a counter entry cannot be reversed in turn', function () {
    [$company, $item, $warehouse, $location] = kardexScenario();

    $entry = registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);
    $reversal = reverseMovement($entry->id);

    reverseMovement($reversal->id);
})->throws(MovementNotReversibleException::class);

test('reversing a movement that does not exist fails', function () {
    reverseMovement((string) Str::uuid7());
})->throws(InventoryMovementNotFoundException::class);

test('a reversal the warehouse cannot cover leaves the original untouched', function () {
    [$company, $item, $warehouse, $location] = kardexScenario(allowsNegative: 'no');

    $entry = registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    /** La mercancía ya salió: revertir la entrada dejaría el saldo negativo. */
    registerInventoryMovement($company, $item, $warehouse, $location, ['type' => 'out', 'quantity' => 10]);

    try {
        reverseMovement($entry->id);
    } catch (\App\Modules\ItemStock\Exceptions\InsufficientStockException) {
        // Se comprueba que la transacción no dejó rastro, no la excepción.
    }

    expect($entry->fresh()->status)->toBe('active');
    expect(InventoryMovement::count())->toBe(2);
});
