<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Models\ClientCollectionApplication;
use App\Modules\ExchangeRate\Models\ExchangeRate;

test('confirming a collection settles its invoices and lowers what the client owes', function () {
    [$user, $company, , $warehouse, $item, $unit] = clientCollectionScenario();

    $client = Client::factory()->create(['company_id' => $company->id]);

    $first = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $second = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    /** Emitir las dos facturas ya dejó al cliente debiendo sus totales. */
    expect((float) $client->refresh()->current_balance)->toBe(400.0);

    $collection = createClientCollection($user, $company, $client, [
        'amount' => 250,
        'applications' => [
            ['sales_invoice_id' => $first->id, 'applied_amount' => 200],
            ['sales_invoice_id' => $second->id, 'applied_amount' => 50],
        ],
    ]);

    $response = moveClientCollectionTo($user, $company, $collection, 'confirmed');

    $response->assertRedirect(route('client-collections.show', ['company' => $company->id, 'id' => $collection->id]));
    $response->assertSessionHasNoErrors();

    expect($collection->refresh()->status)->toBe('confirmed');

    $first->refresh();
    expect((float) $first->paid_amount)->toBe(200.0);
    expect((float) $first->balance)->toBe(0.0);
    expect($first->payment_status)->toBe('paid');

    $second->refresh();
    expect((float) $second->paid_amount)->toBe(50.0);
    expect((float) $second->balance)->toBe(150.0);
    expect($second->payment_status)->toBe('partial');

    expect((float) $client->refresh()->current_balance)->toBe(150.0);
});

test('confirming stamps the exchange difference against the rate of each invoice', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    /** La factura congela 36,50 el día que se emite. */
    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    expect((float) $invoice->exchange_rate)->toBe(36.5);

    /** El cobro se registra otro día, con la tasa en 38,00. */
    ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 38.0]);

    app()->forgetScopedInstances();

    $collection = createClientCollection($user, $company, $client, [
        'amount' => 200,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    expect((float) $collection->exchange_rate)->toBe(38.0);
    expect((float) $collection->amount_ves)->toBe(7600.0);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();

    $application = ClientCollectionApplication::where('source_id', $collection->id)->firstOrFail();
    expect((float) $application->exchange_rate)->toBe(38.0);
    expect((float) $application->exchange_difference)->toBe(300.0);
    expect($application->status)->toBe('active');
});

test('confirming does not recalculate the frozen rate', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client);
    expect((float) $collection->exchange_rate)->toBe(36.5);

    ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 99.0]);

    app()->forgetScopedInstances();

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();

    $collection->refresh();
    expect((float) $collection->exchange_rate)->toBe(36.5);
    expect((float) $collection->amount_ves)->toBe(9125.0);
});

test('an invoice already settled by another collection blocks the confirmation', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $first = createClientCollection($user, $company, $client, [
        'amount' => 200,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    $second = createClientCollection($user, $company, $client, [
        'amount' => 200,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    moveClientCollectionTo($user, $company, $first, 'confirmed')->assertSessionHasNoErrors();

    moveClientCollectionTo($user, $company, $second, 'confirmed')
        ->assertSessionHasErrors('applications.0.sales_invoice_id');

    expect($second->refresh()->status)->toBe('draft');
    expect((float) $invoice->refresh()->paid_amount)->toBe(200.0);
});

test('cancelling a confirmed collection gives the balance back', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $collection = createClientCollection($user, $company, $client, [
        'amount' => 200,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();
    expect((float) $client->refresh()->current_balance)->toBe(0.0);

    moveClientCollectionTo($user, $company, $collection, 'cancelled', [
        'cancellation_reason' => 'La transferencia fue rechazada por el banco.',
    ])->assertSessionHasNoErrors();

    $collection->refresh();
    expect($collection->status)->toBe('cancelled');
    expect($collection->cancelled_at)->not->toBeNull();

    $invoice->refresh();
    expect((float) $invoice->paid_amount)->toBe(0.0);
    expect((float) $invoice->balance)->toBe(200.0);
    expect($invoice->payment_status)->toBe('pending');

    expect((float) $client->refresh()->current_balance)->toBe(200.0);

    /** La fila no se borra: queda como revertida. */
    $application = ClientCollectionApplication::where('source_id', $collection->id)->firstOrFail();
    expect($application->status)->toBe('reversed');
});

test('cancelling a draft collection reverses nothing', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $collection = createClientCollection($user, $company, $client, [
        'amount' => 200,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    moveClientCollectionTo($user, $company, $collection, 'cancelled', [
        'cancellation_reason' => 'Se registró dos veces.',
    ])->assertSessionHasNoErrors();

    expect($collection->refresh()->status)->toBe('cancelled');
    expect((float) $invoice->refresh()->paid_amount)->toBe(0.0);
});

test('cancelling requires a reason', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client);

    moveClientCollectionTo($user, $company, $collection, 'cancelled')
        ->assertSessionHasErrors('cancellation_reason');

    expect($collection->refresh()->status)->toBe('draft');
});

test('a forbidden transition is rejected', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client);

    moveClientCollectionTo($user, $company, $collection, 'completed')
        ->assertSessionHasErrors('status');

    expect($collection->refresh()->status)->toBe('draft');
});

test('a cancelled collection is a dead end', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = ClientCollection::factory()->cancelled()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')
        ->assertSessionHasErrors('status');

    expect($collection->refresh()->status)->toBe('cancelled');
});

test('a user without permission cannot change the status', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client);

    assignRoleWithPermissions($user, $company, ['client-collections.list']);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertForbidden();
});
