<?php

declare(strict_types=1);

use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ClientCollection\Models\ClientCollection;

test('approving the advance creates its mirror collection in draft', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client, [
        'amount' => 400,
        'payment_method' => 'check',
        'reference' => 'CH-9981',
        'bank_account' => 'Banco Nacional 0102',
    ]);

    $response = moveClientAdvanceTo($user, $company, $advance, 'pending_confirmation');

    $response->assertRedirect(route('client-advances.show', ['company' => $company->id, 'id' => $advance->id]));
    $response->assertSessionHasNoErrors();

    expect($advance->refresh()->status)->toBe('pending_confirmation');

    $collection = mirrorCollectionOf($advance);
    expect($collection)->not->toBeNull();
    expect($collection->code)->toBe('COB000001');
    expect($collection->status)->toBe('draft');
    expect($collection->origin_type)->toBe('advance');
    expect($collection->origin_id)->toBe($advance->id);
    expect($collection->client_id)->toBe($client->id);
    expect($collection->payment_method)->toBe('check');
    expect($collection->reference)->toBe('CH-9981');
    expect($collection->bank_account)->toBe('Banco Nacional 0102');
    expect((float) $collection->amount)->toBe(400.0);
    expect((float) $collection->exchange_rate)->toBe((float) $advance->exchange_rate);
    /** Nace sin aplicaciones: el anticipo no cancela ninguna factura. */
    expect((float) $collection->applied_amount)->toBe(0.0);
    expect((float) $collection->unapplied_amount)->toBe(400.0);
});

/** Comprometido, no recibido: el crédito no existe hasta que el cobro se confirme. */
test('an approved advance does not give credit to the client yet', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);
    moveClientAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    expect((float) $client->refresh()->advance_balance)->toBe(0.0);
});

test('confirming the mirror collection receives the advance and credits the client', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = confirmedClientAdvance($user, $company, $client, ['amount' => 400]);

    expect($advance->status)->toBe('confirmed');
    expect((float) $advance->balance)->toBe(400.0);
    expect((float) $client->refresh()->advance_balance)->toBe(400.0);
    /** El anticipo no cancela deuda por sí solo: no toca el saldo por cobrar. */
    expect((float) $client->current_balance)->toBe(0.0);
});

test('cancelling the mirror collection sends the advance back to draft', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = confirmedClientAdvance($user, $company, $client, ['amount' => 400]);

    moveClientCollectionTo($user, $company, mirrorCollectionOf($advance), 'cancelled', [
        'cancellation_reason' => 'El cliente pidió reversar la transferencia.',
    ])->assertSessionHasNoErrors();

    $advance->refresh();
    expect($advance->status)->toBe('draft');
    /** Conserva su código: es el mismo anticipo, corregido. */
    expect($advance->code)->toBe('ANC000001');
    expect((float) $client->refresh()->advance_balance)->toBe(0.0);
});

test('cancelling a draft mirror collection also frees the advance', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);
    moveClientAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    moveClientCollectionTo($user, $company, mirrorCollectionOf($advance), 'cancelled', [
        'cancellation_reason' => 'La transferencia nunca llegó.',
    ])->assertSessionHasNoErrors();

    expect($advance->refresh()->status)->toBe('draft');
    expect((float) $client->refresh()->advance_balance)->toBe(0.0);
});

/** §5.1: el cheque devuelto anula el cobro, y con él vuelve el anticipo a borrador. */
test('a bounced cheque on the mirror collection sends the advance back to draft', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = confirmedClientAdvance($user, $company, $client, [
        'amount' => 400,
        'payment_method' => 'check',
        'reference' => 'CH-9981',
    ]);

    expect((float) $client->refresh()->advance_balance)->toBe(400.0);

    moveClientCollectionCheckTo($user, $company, mirrorCollectionOf($advance), 'bounced')
        ->assertSessionHasNoErrors();

    expect($advance->refresh()->status)->toBe('draft');
    expect((float) $client->refresh()->advance_balance)->toBe(0.0);
});

test('an advance back in draft can be approved again with a new collection', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);
    moveClientAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    $first = mirrorCollectionOf($advance);
    moveClientCollectionTo($user, $company, $first, 'cancelled', [
        'cancellation_reason' => 'Se cobró por otra vía.',
    ])->assertSessionHasNoErrors();

    moveClientAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    expect($advance->refresh()->status)->toBe('pending_confirmation');

    $collections = ClientCollection::query()
        ->where('origin_id', $advance->id)
        ->where('status', '!=', 'cancelled')
        ->get();

    expect($collections)->toHaveCount(1);
    expect($collections->first()->id)->not->toBe($first->id);
});

test('cancelling the advance cancels its mirror collection', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);
    moveClientAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    moveClientAdvanceTo($user, $company, $advance, 'cancelled')->assertSessionHasNoErrors();

    $advance->refresh();
    expect($advance->status)->toBe('cancelled');
    expect($advance->cancelled_at)->not->toBeNull();

    $collection = mirrorCollectionOf($advance);
    expect($collection->status)->toBe('cancelled');
    expect($collection->cancellation_reason)->toBe("El anticipo {$advance->code} fue anulado.");
});

test('a draft advance is cancelled without any collection behind it', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);

    moveClientAdvanceTo($user, $company, $advance, 'cancelled')->assertSessionHasNoErrors();

    expect($advance->refresh()->status)->toBe('cancelled');
    expect(ClientCollection::count())->toBe(0);
});

test('a received advance is no longer cancelled from its own screen', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = confirmedClientAdvance($user, $company, $client);

    moveClientAdvanceTo($user, $company, $advance, 'cancelled')
        ->assertSessionHasErrors('status');

    expect($advance->refresh()->status)->toBe('confirmed');
});

test('an advance with credit already applied cannot be cancelled', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);
    $advance->update(['applied_amount' => 100, 'balance' => 300]);

    moveClientAdvanceTo($user, $company, $advance, 'cancelled')
        ->assertSessionHasErrors('status');

    expect($advance->refresh()->status)->toBe('draft');
});

test('the screen cannot confirm an advance by itself', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);
    moveClientAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    moveClientAdvanceTo($user, $company, $advance, 'confirmed')
        ->assertSessionHasErrors('status');

    expect($advance->refresh()->status)->toBe('pending_confirmation');
    expect((float) $client->refresh()->advance_balance)->toBe(0.0);
});

test('a cancelled advance is a dead end', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = ClientAdvance::factory()->cancelled()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    moveClientAdvanceTo($user, $company, $advance, 'pending_confirmation')
        ->assertSessionHasErrors('status');

    expect($advance->refresh()->status)->toBe('cancelled');
});

test('the mirror collection is not editable', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);
    moveClientAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    $collection = mirrorCollectionOf($advance);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-collections.update', ['company' => $company->id, 'id' => $collection->id]),
            [
                'client_id' => $client->id,
                'collection_date' => now()->toDateString(),
                'payment_method' => 'cash',
                'currency' => 'USD',
                'amount' => 999,
                'applications' => [],
            ],
        )
        ->assertSessionHasErrors('origin_type');

    expect((float) $collection->refresh()->amount)->toBe(400.0);
});

test('a user without permission cannot change the status', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);

    assignRoleWithPermissions($user, $company, ['client-advances.list']);

    moveClientAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertForbidden();
});
