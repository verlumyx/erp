<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;

use function Pest\Laravel\actingAs;

/**
 * Los dos endpoints de opciones que alimentan la pantalla de cobros: el select
 * de clientes con deuda y el de facturas con saldo.
 */
test('the client lookup can offer only the ones that owe money', function () {
    [$user, $company] = clientCollectionScenario();

    $owed = Client::factory()->withBalance(500)->create([
        'company_id' => $company->id,
        'name' => 'Cliente con deuda',
    ]);

    Client::factory()->create([
        'company_id' => $company->id,
        'name' => 'Cliente al día',
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('clients.lookup', ['company' => $company->id, 'with_balance' => 'yes']));

    $response->assertOk();

    $values = array_column($response->json('data'), 'value');
    expect($values)->toBe([$owed->id]);
});

test('the client option carries the two indicators of the collection screen', function () {
    [$user, $company] = clientCollectionScenario();

    $client = Client::factory()->withBalance(500)->create([
        'company_id' => $company->id,
        'advance_balance' => 120,
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('clients.lookup', ['company' => $company->id, 'ids' => $client->id]));

    $response->assertOk();
    expect((float) $response->json('data.0.meta.current_balance'))->toBe(500.0);
    expect((float) $response->json('data.0.meta.advance_balance'))->toBe(120.0);
});

test('the invoice lookup can offer only the ones that still owe something', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientCollectionScenario();

    $open = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    /** Una factura saldada por un cobro confirmado ya no se ofrece. */
    $settled = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $collection = createClientCollection($user, $company, $client, [
        'amount' => 200,
        'applications' => [
            ['sales_invoice_id' => $settled->id, 'applied_amount' => 200],
        ],
    ]);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales-invoices.lookup', [
            'company' => $company->id,
            'client_id' => $client->id,
            'open' => 'yes',
        ]));

    $response->assertOk();

    $values = array_column($response->json('data'), 'value');
    expect($values)->toBe([$open->id]);
    expect((float) $response->json('data.0.meta.balance'))->toBe(200.0);
    expect((float) $response->json('data.0.meta.exchange_rate'))->toBe(36.5);
});

test('a user without permission cannot use the invoice lookup', function () {
    [$user, $company] = clientCollectionScenario();

    assignRoleWithPermissions($user, $company, ['client-collections.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales-invoices.lookup', ['company' => $company->id]))
        ->assertForbidden();
});
