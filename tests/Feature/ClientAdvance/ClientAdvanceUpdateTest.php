<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;

use function Pest\Laravel\actingAs;

test('a draft advance can be edited', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-advances.update', ['company' => $company->id, 'id' => $advance->id]),
            clientAdvancePayload($client, [
                'amount' => 650,
                'payment_method' => 'cash',
                'reference' => 'Recibo 44',
                'notes' => 'Se aumentó el abono acordado.',
            ]),
        );

    $response->assertRedirect(route('client-advances.show', ['company' => $company->id, 'id' => $advance->id]));
    $response->assertSessionHasNoErrors();

    $advance->refresh();
    expect((float) $advance->amount)->toBe(650.0);
    expect((float) $advance->balance)->toBe(650.0);
    expect($advance->payment_method)->toBe('cash');
    expect($advance->reference)->toBe('Recibo 44');
    /** Editar no le cambia el código ni lo saca de borrador. */
    expect($advance->code)->toBe('ANC000001');
    expect($advance->status)->toBe('draft');
});

test('an approved advance is no longer editable', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);
    moveClientAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-advances.update', ['company' => $company->id, 'id' => $advance->id]),
            clientAdvancePayload($client, ['amount' => 999]),
        )
        ->assertSessionHasErrors('status');

    expect((float) $advance->refresh()->amount)->toBe(400.0);
});

test('the edit form is rendered for a draft advance', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-advances.edit', ['company' => $company->id, 'id' => $advance->id]))
        ->assertOk()
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('client-advances/edit')
            ->where('clientAdvance.code', 'ANC000001'));
});

test('the advance cannot be moved to a client of another company', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);
    $stranger = Client::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-advances.update', ['company' => $company->id, 'id' => $advance->id]),
            clientAdvancePayload($stranger),
        )
        ->assertSessionHasErrors('client_id');

    expect($advance->refresh()->client_id)->toBe($client->id);
});

test('a user without permission cannot edit an advance', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);

    assignRoleWithPermissions($user, $company, ['client-advances.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('client-advances.update', ['company' => $company->id, 'id' => $advance->id]),
            clientAdvancePayload($client),
        )
        ->assertForbidden();
});
