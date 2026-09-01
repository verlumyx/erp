<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\ClientAdvance\Models\ClientAdvance;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('a client advance can be created', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $payload = clientAdvancePayload($client, [
        'reference' => '0102-4471',
        'bank_account' => 'Banco Nacional 0102',
        'notes' => 'Abono del 40% acordado con el cliente.',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('client-advances.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('client-advances.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $advance = ClientAdvance::find($payload['id']);
    expect($advance)->not->toBeNull();
    expect($advance->code)->toBe('ANC000001');
    expect($advance->status)->toBe('draft');
    expect($advance->company_id)->toBe($company->id);
    expect($advance->created_by)->toBe($user->id);
    expect($advance->client_id)->toBe($client->id);
    expect($advance->reference)->toBe('0102-4471');
    expect((float) $advance->amount)->toBe(400.0);
    expect((float) $advance->applied_amount)->toBe(0.0);
    expect((float) $advance->refunded_amount)->toBe(0.0);
    /** Nace entero disponible: nada se ha aplicado todavía. */
    expect((float) $advance->balance)->toBe(400.0);
});

/** Capturarlo no compromete nada: el crédito lo da confirmar su cobro. */
test('a draft advance does not touch the credit of the client', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    createClientAdvance($user, $company, $client);

    expect((float) $client->refresh()->advance_balance)->toBe(0.0);
});

test('the advance can stem from a sales order of the same client', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientAdvanceScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $advance = createClientAdvance($user, $company, $client, [
        'sales_order_id' => $order->id,
    ]);

    expect($advance->sales_order_id)->toBe($order->id);
});

test('the order of another client is rejected', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientAdvanceScenario();

    $other = Client::factory()->create(['company_id' => $company->id]);
    $order = createSalesOrder($user, $company, $other, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-advances.store', ['company' => $company->id]),
            clientAdvancePayload($client, ['sales_order_id' => $order->id]),
        )
        ->assertSessionHasErrors('sales_order_id');

    expect(ClientAdvance::count())->toBe(0);
});

test('the amount of the advance is required and positive', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-advances.store', ['company' => $company->id]),
            clientAdvancePayload($client, ['amount' => 0]),
        )
        ->assertSessionHasErrors('amount');
});

test('the client of another company is not available', function () {
    [$user, $company] = clientAdvanceScenario();

    $stranger = Client::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-advances.store', ['company' => $company->id]),
            clientAdvancePayload($stranger),
        )
        ->assertSessionHasErrors('client_id');
});

test('the code is sequential per company', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $first = createClientAdvance($user, $company, $client);
    $second = createClientAdvance($user, $company, $client);

    expect($first->code)->toBe('ANC000001');
    expect($second->code)->toBe('ANC000002');
});

test('the create form is rendered', function () {
    [$user, $company] = clientAdvanceScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-advances.create', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('client-advances/create'));
});

test('a user without permission cannot create an advance', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    assignRoleWithPermissions($user, $company, ['client-advances.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-advances.store', ['company' => $company->id]),
            clientAdvancePayload($client),
        )
        ->assertForbidden();
});
