<?php

declare(strict_types=1);

use App\Modules\ClientCollection\Models\ClientCollectionApplication;

/**
 * El cheque avanza por su propio carril: depositarlo o conformarlo no toca el
 * cobro. Que rebote sí (`docs/ventas.md` §6.3).
 */
test('the cheque moves along its own track without touching the collection', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client, [
        'payment_method' => 'check',
        'check_number' => '00012345',
    ]);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();

    moveClientCollectionCheckTo($user, $company, $collection, 'deposited')->assertSessionHasNoErrors();
    expect($collection->refresh()->check_status)->toBe('deposited');
    expect($collection->status)->toBe('confirmed');

    moveClientCollectionCheckTo($user, $company, $collection, 'cleared')->assertSessionHasNoErrors();
    expect($collection->refresh()->check_status)->toBe('cleared');
    expect($collection->status)->toBe('confirmed');
});

test('a bounced cheque reverses the applications and gives the balance back', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $collection = createClientCollection($user, $company, $client, [
        'payment_method' => 'check',
        'check_number' => '00012345',
        'amount' => 200,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();
    expect((float) $client->refresh()->current_balance)->toBe(0.0);

    moveClientCollectionCheckTo($user, $company, $collection, 'bounced')->assertSessionHasNoErrors();

    $collection->refresh();
    expect($collection->check_status)->toBe('bounced');
    /** El dinero nunca entró: el cobro queda anulado. */
    expect($collection->status)->toBe('cancelled');
    expect($collection->cancellation_reason)->toBe('El cheque fue devuelto por el banco.');

    $invoice->refresh();
    expect((float) $invoice->paid_amount)->toBe(0.0);
    expect((float) $invoice->balance)->toBe(200.0);

    expect((float) $client->refresh()->current_balance)->toBe(200.0);

    $application = ClientCollectionApplication::where('source_id', $collection->id)->firstOrFail();
    expect($application->status)->toBe('reversed');
});

test('a bounced cheque on a draft collection reverses nothing but still voids it', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $collection = createClientCollection($user, $company, $client, [
        'payment_method' => 'check',
        'check_number' => '00012345',
        'amount' => 200,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    moveClientCollectionCheckTo($user, $company, $collection, 'bounced')->assertSessionHasNoErrors();

    expect($collection->refresh()->status)->toBe('cancelled');
    expect((float) $invoice->refresh()->paid_amount)->toBe(0.0);
});

test('a collection received in cash has no cheque to move', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client, ['payment_method' => 'cash']);

    moveClientCollectionCheckTo($user, $company, $collection, 'deposited')
        ->assertSessionHasErrors('check_status');

    expect($collection->refresh()->check_status)->toBeNull();
});

test('a cancelled collection no longer moves its cheque', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client, [
        'payment_method' => 'check',
        'check_number' => '00012345',
    ]);

    moveClientCollectionTo($user, $company, $collection, 'cancelled', [
        'cancellation_reason' => 'Se registró dos veces.',
    ])->assertSessionHasNoErrors();

    moveClientCollectionCheckTo($user, $company, $collection, 'deposited')
        ->assertSessionHasErrors('check_status');
});

test('a user without permission cannot move the cheque', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client, [
        'payment_method' => 'check',
        'check_number' => '00012345',
    ]);

    assignRoleWithPermissions($user, $company, ['client-collections.list']);

    moveClientCollectionCheckTo($user, $company, $collection, 'deposited')->assertForbidden();
});
