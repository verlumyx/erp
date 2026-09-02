<?php

declare(strict_types=1);

use App\Modules\ClientCollection\Models\ClientCollectionApplication;

/** Filas del reparto de un origen cualquiera. */
function applicationsOfSource(string $sourceType, string $sourceId): \Illuminate\Database\Eloquent\Collection
{
    return ClientCollectionApplication::query()
        ->where('source_type', $sourceType)
        ->where('source_id', $sourceId)
        ->orderBy('created_at')
        ->get();
}

test('a collection paid with an advance spends the credit of the client', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    expect((float) $invoice->balance)->toBe(200.0);

    $advance = confirmedClientAdvance($user, $company, $client, ['amount' => 400]);

    expect($advance->status)->toBe('confirmed');
    expect((float) $advance->balance)->toBe(400.0);
    expect((float) $client->refresh()->advance_balance)->toBe(400.0);
    expect((float) $client->current_balance)->toBe(200.0);

    $collection = createClientCollection($user, $company, $client, [
        'payment_method' => 'advance',
        'credit_source_id' => $advance->id,
        'amount' => 200,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    expect($collection->credit_source_id)->toBe($advance->id);

    /** El reparto viaja con el `source_type` del crédito, no con el del cobro. */
    expect(applicationsOfSource('advance', $advance->id))->toHaveCount(1);
    expect(applicationsOfSource('collection', $collection->id))->toHaveCount(0);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();

    $invoice->refresh();
    expect((float) $invoice->paid_amount)->toBe(200.0);
    expect((float) $invoice->balance)->toBe(0.0);
    expect($invoice->payment_status)->toBe('paid');

    $advance->refresh();
    expect((float) $advance->applied_amount)->toBe(200.0);
    expect((float) $advance->balance)->toBe(200.0);
    expect($advance->status)->toBe('partial');

    $client->refresh();
    expect((float) $client->current_balance)->toBe(0.0);
    expect((float) $client->advance_balance)->toBe(200.0);
});

test('an advance spent to the last cent is completed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $advance = confirmedClientAdvance($user, $company, $client, ['amount' => 200]);

    $collection = createClientCollection($user, $company, $client, [
        'payment_method' => 'advance',
        'credit_source_id' => $advance->id,
        'amount' => 200,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();

    $advance->refresh();
    expect((float) $advance->balance)->toBe(0.0);
    expect($advance->status)->toBe('completed');
});

test('a collection cannot spend more credit than the advance has left', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $advance = confirmedClientAdvance($user, $company, $client, ['amount' => 50]);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('client-collections.store', ['company' => $company->id]), clientCollectionPayload($client, [
            'payment_method' => 'advance',
            'credit_source_id' => $advance->id,
            'amount' => 200,
            'applications' => [
                ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
            ],
        ]))
        ->assertSessionHasErrors('credit_source_id');
});

test('a collection paid with credit has to say which credit', function () {
    [$user, $company, $client] = clientCollectionScenario();

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('client-collections.store', ['company' => $company->id]), clientCollectionPayload($client, [
            'payment_method' => 'advance',
        ]))
        ->assertSessionHasErrors('credit_source_id');
});

test('a collection with money of its own spends no credit', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $advance = confirmedClientAdvance($user, $company, $client, ['amount' => 400]);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('client-collections.store', ['company' => $company->id]), clientCollectionPayload($client, [
            'payment_method' => 'transfer',
            'credit_source_id' => $advance->id,
        ]))
        ->assertSessionHasErrors('credit_source_id');
});

test('cancelling a collection gives the advance its credit back', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $advance = confirmedClientAdvance($user, $company, $client, ['amount' => 400]);

    $collection = createClientCollection($user, $company, $client, [
        'payment_method' => 'advance',
        'credit_source_id' => $advance->id,
        'amount' => 200,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();

    moveClientCollectionTo($user, $company, $collection, 'cancelled', [
        'cancellation_reason' => 'Se aplicó al anticipo equivocado.',
    ])->assertSessionHasNoErrors();

    $invoice->refresh();
    expect((float) $invoice->balance)->toBe(200.0);

    $advance->refresh();
    expect((float) $advance->applied_amount)->toBe(0.0);
    expect((float) $advance->balance)->toBe(400.0);
    expect($advance->status)->toBe('confirmed');

    $client->refresh();
    expect((float) $client->current_balance)->toBe(200.0);
    expect((float) $client->advance_balance)->toBe(400.0);
});

test('a collection paid with a credit note does not lower the client balance twice', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $first = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $second = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    expect((float) $client->refresh()->current_balance)->toBe(400.0);

    /** Una nota suelta: baja el saldo del cliente y queda como crédito. */
    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 60,
        ]],
    ]);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect((float) $client->refresh()->current_balance)->toBe(340.0);
    expect((float) $note->refresh()->balance)->toBe(60.0);

    $collection = createClientCollection($user, $company, $client, [
        'payment_method' => 'credit_note',
        'credit_source_id' => $note->id,
        'amount' => 60,
        'applications' => [
            ['sales_invoice_id' => $second->id, 'applied_amount' => 60],
        ],
    ]);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();

    expect((float) $second->refresh()->balance)->toBe(140.0);
    expect((float) $first->refresh()->balance)->toBe(200.0);

    /** La nota ya lo había bajado: aplicarla no vuelve a bajarlo. */
    expect((float) $client->refresh()->current_balance)->toBe(340.0);

    $note->refresh();
    expect((float) $note->applied_amount)->toBe(60.0);
    expect($note->status)->toBe('completed');
});

test('a collection remembers the route it was collected on', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $route = \App\Modules\Route\Models\Route::factory()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
    ]);

    $collection = createClientCollection($user, $company, $client, [
        'route_id' => $route->id,
    ]);

    expect($collection->route_id)->toBe($route->id);
    expect($collection->route->name)->toBe($route->name);
});
