<?php

declare(strict_types=1);

use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ClientCollection\Models\ClientCollection;

/** El anticipo que nació del excedente de un cobro. */
function surplusAdvanceOf(ClientCollection $collection): ?ClientAdvance
{
    return ClientAdvance::query()
        ->where('origin_collection_id', $collection->id)
        ->first();
}

test('what is collected and not distributed becomes a confirmed advance', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    expect((float) $invoice->balance)->toBe(200.0);

    $collection = createClientCollection($user, $company, $client, [
        'amount' => 250,
        'reference' => 'TRF-99881',
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    expect((float) $collection->unapplied_amount)->toBe(50.0);

    /** En borrador el dinero no entró: no hay anticipo todavía. */
    expect(surplusAdvanceOf($collection))->toBeNull();

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();

    $advance = surplusAdvanceOf($collection);
    expect($advance)->not->toBeNull();
    expect($advance->code)->toStartWith('ANC');
    /** Ya recibido: el dinero entró con el cobro, no vuelve a aprobarse. */
    expect($advance->status)->toBe('confirmed');
    expect((float) $advance->amount)->toBe(50.0);
    expect((float) $advance->balance)->toBe(50.0);
    expect($advance->client_id)->toBe($client->id);
    expect($advance->currency)->toBe($collection->currency);
    expect((float) $advance->exchange_rate)->toBe((float) $collection->exchange_rate);
    expect($advance->reference)->toBe('TRF-99881');

    /** Y no genera un segundo `COB`: el dinero ya entró con este. */
    expect(ClientCollection::query()->where('origin_id', $advance->id)->count())->toBe(0);

    $client->refresh();
    expect((float) $client->advance_balance)->toBe(50.0);
    expect((float) $client->current_balance)->toBe(0.0);
});

test('a collection that distributes everything generates no advance', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $collection = createClientCollection($user, $company, $client, [
        'amount' => 200,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    expect((float) $collection->unapplied_amount)->toBe(0.0);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();

    expect(surplusAdvanceOf($collection))->toBeNull();
    expect((float) $client->refresh()->advance_balance)->toBe(0.0);
});

test('a collection with no distribution at all becomes an advance for the whole amount', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client, ['amount' => 300]);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();

    $advance = surplusAdvanceOf($collection);
    expect((float) $advance->amount)->toBe(300.0);
    expect((float) $client->refresh()->advance_balance)->toBe(300.0);
});

test('the mirror collection of an advance is exempt', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $advance = confirmedClientAdvance($user, $company, $client, ['amount' => 400]);

    $mirror = mirrorCollectionOf($advance);

    /** `unapplied_amount = amount` por definición, y el anticipo ya existe. */
    expect((float) $mirror->unapplied_amount)->toBe(400.0);
    expect(surplusAdvanceOf($mirror))->toBeNull();

    /** El cliente tiene 400 de crédito, no 800. */
    expect((float) $client->refresh()->advance_balance)->toBe(400.0);
});

test('a collection paid with credit generates no advance for what it leaves unspent', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $source = confirmedClientAdvance($user, $company, $client, ['amount' => 400]);

    $collection = createClientCollection($user, $company, $client, [
        'payment_method' => 'advance',
        'credit_source_id' => $source->id,
        'amount' => 400,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    expect((float) $collection->unapplied_amount)->toBe(200.0);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();

    /** Lo que no se gastó sigue en el anticipo de origen, no en uno nuevo. */
    expect(surplusAdvanceOf($collection))->toBeNull();
    expect((float) $source->refresh()->balance)->toBe(200.0);
    expect((float) $client->refresh()->advance_balance)->toBe(200.0);
});

test('cancelling the collection cancels the advance it generated', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $collection = createClientCollection($user, $company, $client, [
        'amount' => 250,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();
    expect((float) $client->refresh()->advance_balance)->toBe(50.0);

    moveClientCollectionTo($user, $company, $collection, 'cancelled', [
        'cancellation_reason' => 'La transferencia fue rechazada por el banco.',
    ])->assertSessionHasNoErrors();

    $advance = surplusAdvanceOf($collection);
    expect($advance->status)->toBe('cancelled');
    expect($advance->cancelled_at)->not->toBeNull();

    $client->refresh();
    expect((float) $client->advance_balance)->toBe(0.0);
    expect((float) $client->current_balance)->toBe(200.0);
    expect((float) $invoice->refresh()->balance)->toBe(200.0);
});
