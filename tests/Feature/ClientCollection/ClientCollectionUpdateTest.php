<?php

declare(strict_types=1);

use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Models\ClientCollectionApplication;

use function Pest\Laravel\actingAs;

test('a draft collection can be updated', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-collections.update', ['company' => $company->id, 'id' => $collection->id]),
            clientCollectionPayload($client, [
                'amount' => 400,
                'payment_method' => 'check',
                'check_number' => '0001234',
                'reference' => '0001234',
                'notes' => 'Se cobró con cheque, no por transferencia.',
            ]),
        );

    $response->assertRedirect(route('client-collections.show', ['company' => $company->id, 'id' => $collection->id]));
    $response->assertSessionHasNoErrors();

    $collection->refresh();
    expect((float) $collection->amount)->toBe(400.0);
    expect($collection->payment_method)->toBe('check');
    expect($collection->check_number)->toBe('0001234');
    expect($collection->check_status)->toBe('pending');
    expect($collection->code)->toBe('COB000001');
});

test('dropping an invoice from the distribution reverses its row instead of deleting it', function () {
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

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-collections.update', ['company' => $company->id, 'id' => $collection->id]),
            clientCollectionPayload($client, [
                'amount' => 250,
                'applications' => [
                    ['sales_invoice_id' => $first->id, 'applied_amount' => 150],
                ],
            ]),
        )
        ->assertSessionHasNoErrors();

    expect(ClientCollectionApplication::count())->toBe(2);

    $kept = ClientCollectionApplication::where('sales_invoice_id', $first->id)->first();
    expect($kept->status)->toBe('active');
    expect((float) $kept->applied_amount)->toBe(150.0);

    $dropped = ClientCollectionApplication::where('sales_invoice_id', $second->id)->first();
    expect($dropped->status)->toBe('reversed');

    expect((float) $collection->refresh()->applied_amount)->toBe(150.0);
    expect((float) $collection->unapplied_amount)->toBe(100.0);
});

test('a confirmed collection can no longer be edited', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-collections.update', ['company' => $company->id, 'id' => $collection->id]),
            clientCollectionPayload($client, ['amount' => 999]),
        )
        ->assertSessionHasErrors('status');

    expect((float) $collection->refresh()->amount)->toBe(250.0);
});

test('the mirror collection of an advance is not editable', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = ClientCollection::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'origin_type' => 'advance',
        'origin_id' => (string) Illuminate\Support\Str::uuid7(),
        'status' => 'draft',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-collections.update', ['company' => $company->id, 'id' => $collection->id]),
            clientCollectionPayload($client, ['amount' => 999]),
        )
        ->assertSessionHasErrors('origin_type');
});

test('the origin cannot be changed on update', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $collection = createClientCollection($user, $company, $client);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-collections.update', ['company' => $company->id, 'id' => $collection->id]),
            clientCollectionPayload($client, [
                'origin_type' => 'invoice',
                'origin_id' => $invoice->id,
            ]),
        )
        ->assertSessionHasNoErrors();

    $collection->refresh();
    expect($collection->origin_type)->toBe('client');
    expect($collection->origin_id)->toBeNull();
});

test('a user without permission cannot update a collection', function () {
    [$user, $company, $client] = clientCollectionScenario();

    $collection = createClientCollection($user, $company, $client);

    assignRoleWithPermissions($user, $company, ['client-collections.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-collections.update', ['company' => $company->id, 'id' => $collection->id]),
            clientCollectionPayload($client),
        )
        ->assertForbidden();
});
