<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Models\ClientCollectionApplication;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('a client collection can be created', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $payload = clientCollectionPayload($client, [
        'reference' => '0102-9987',
        'bank_account' => 'Banco Nacional 0102',
        'collected_by' => $user->id,
        'notes' => 'Transferencia del viernes.',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('client-collections.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('client-collections.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $collection = ClientCollection::find($payload['id']);
    expect($collection)->not->toBeNull();
    expect($collection->code)->toBe('COB000001');
    expect($collection->status)->toBe('draft');
    expect($collection->company_id)->toBe($company->id);
    expect($collection->created_by)->toBe($user->id);
    expect($collection->client_id)->toBe($client->id);
    expect($collection->origin_type)->toBe('client');
    expect($collection->origin_id)->toBeNull();
    expect($collection->reference)->toBe('0102-9987');
    expect($collection->collected_by)->toBe($user->id);
    expect((float) $collection->amount)->toBe(250.0);
    expect((float) $collection->applied_amount)->toBe(0.0);
    expect((float) $collection->unapplied_amount)->toBe(250.0);
});

test('the collection distributes its amount among the invoices of the client', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $first = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $second = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $collection = createClientCollection($user, $company, $client, [
        'amount' => 250,
        'applications' => [
            ['sales_invoice_id' => $first->id, 'applied_amount' => 200],
            ['sales_invoice_id' => $second->id, 'applied_amount' => 50],
        ],
    ]);

    expect((float) $collection->applied_amount)->toBe(250.0);
    expect((float) $collection->unapplied_amount)->toBe(0.0);
    expect($collection->applications)->toHaveCount(2);

    /** Escribir el reparto todavía no mueve el saldo: eso lo hace confirmar. */
    expect((float) $first->refresh()->balance)->toBe(200.0);
    expect((float) $second->refresh()->balance)->toBe(200.0);
});

test('a collection started from an invoice freezes that invoice as its origin', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $collection = createClientCollection($user, $company, $client, [
        'origin_type' => 'invoice',
        'origin_id' => $invoice->id,
        'amount' => 200,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    expect($collection->origin_type)->toBe('invoice');
    expect($collection->origin_id)->toBe($invoice->id);
});

test('a collection started from an invoice needs that invoice', function () {
    [$user, $company, $client] = clientCollectionScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-collections.store', ['company' => $company->id]),
            clientCollectionPayload($client, ['origin_type' => 'invoice']),
        )
        ->assertSessionHasErrors('origin_id');
});

test('the mirror collection of an advance is not created from this screen', function () {
    [$user, $company, $client] = clientCollectionScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-collections.store', ['company' => $company->id]),
            clientCollectionPayload($client, ['origin_type' => 'advance']),
        )
        ->assertSessionHasErrors('origin_type');

    expect(ClientCollection::count())->toBe(0);
});

test('a collection does not cross clients', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $other = Client::factory()->create(['company_id' => $company->id]);
    $invoice = collectibleSalesInvoice($user, $company, $other, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-collections.store', ['company' => $company->id]),
            clientCollectionPayload($client, [
                'applications' => [
                    ['sales_invoice_id' => $invoice->id, 'applied_amount' => 100],
                ],
            ]),
        )
        ->assertSessionHasErrors('applications.0.sales_invoice_id');

    expect(ClientCollection::count())->toBe(0);
});

test('a draft invoice does not admit collections yet', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-collections.store', ['company' => $company->id]),
            clientCollectionPayload($client, [
                'applications' => [
                    ['sales_invoice_id' => $invoice->id, 'applied_amount' => 100],
                ],
            ]),
        )
        ->assertSessionHasErrors('applications.0.sales_invoice_id');
});

test('an application cannot exceed the balance of its invoice', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-collections.store', ['company' => $company->id]),
            clientCollectionPayload($client, [
                'amount' => 500,
                'applications' => [
                    ['sales_invoice_id' => $invoice->id, 'applied_amount' => 250],
                ],
            ]),
        )
        ->assertSessionHasErrors('applications.0.applied_amount');
});

test('the distribution cannot exceed the amount of the collection', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-collections.store', ['company' => $company->id]),
            clientCollectionPayload($client, [
                'amount' => 100,
                'applications' => [
                    ['sales_invoice_id' => $invoice->id, 'applied_amount' => 250],
                ],
            ]),
        )
        ->assertSessionHasErrors('applications');
});

/**
 * La retención que el cliente practica cancela deuda sin entrar en caja, así
 * que el reparto puede llegar hasta el monto recibido más lo retenido.
 */
test('the withholding widens what the collection can settle', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $collection = createClientCollection($user, $company, $client, [
        'amount' => 160,
        'withholding_amount' => 40,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => 200],
        ],
    ]);

    expect((float) $collection->applied_amount)->toBe(200.0);
    expect((float) $collection->unapplied_amount)->toBe(0.0);
});

test('the same invoice cannot appear twice in the distribution', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-collections.store', ['company' => $company->id]),
            clientCollectionPayload($client, [
                'applications' => [
                    ['sales_invoice_id' => $invoice->id, 'applied_amount' => 100],
                    ['sales_invoice_id' => $invoice->id, 'applied_amount' => 100],
                ],
            ]),
        )
        ->assertSessionHasErrors('applications.1.sales_invoice_id');

    expect(ClientCollectionApplication::count())->toBe(0);
});

test('the amount of the collection is required and positive', function () {
    [$user, $company, $client] = clientCollectionScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-collections.store', ['company' => $company->id]),
            clientCollectionPayload($client, ['amount' => 0]),
        )
        ->assertSessionHasErrors('amount');
});

test('a cheque collection needs its number and starts pending', function () {
    [$user, $company, $client] = clientCollectionScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-collections.store', ['company' => $company->id]),
            clientCollectionPayload($client, ['payment_method' => 'check']),
        )
        ->assertSessionHasErrors('check_number');

    $collection = createClientCollection($user, $company, $client, [
        'payment_method' => 'check',
        'check_number' => '00012345',
        'check_date' => now()->addDays(15)->toDateString(),
    ]);

    expect($collection->check_number)->toBe('00012345');
    expect($collection->check_status)->toBe('pending');
});

test('the cheque data is dropped when the money did not come in a cheque', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client, [
        'payment_method' => 'cash',
        'check_number' => '00012345',
        'check_date' => now()->toDateString(),
    ]);

    expect($collection->check_number)->toBeNull();
    expect($collection->check_status)->toBeNull();
});

test('the code is sequential per company', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $first = createClientCollection($user, $company, $client);
    $second = createClientCollection($user, $company, $client);

    expect($first->code)->toBe('COB000001');
    expect($second->code)->toBe('COB000002');
});

test('the create form is rendered', function () {
    [$user, $company] = clientCollectionScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-collections.create', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('client-collections/create'));
});

test('a user without permission cannot create a collection', function () {
    [$user, $company, $client] = clientCollectionScenario();

    assignRoleWithPermissions($user, $company, ['client-collections.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('client-collections.store', ['company' => $company->id]),
            clientCollectionPayload($client),
        )
        ->assertForbidden();
});
