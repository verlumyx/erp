<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;

use function Pest\Laravel\actingAs;

/** Una nota de la empresa activa en el estado que pida el test. */
function salesCreditNoteInStatus(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    string $status,
    array $overrides = [],
): SalesCreditNote {
    return SalesCreditNote::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'status' => $status,
        ...$overrides,
    ]);
}

test('a draft note can be confirmed', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    $note = salesCreditNoteInStatus($user, $company, $client, 'draft');

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'confirmed',
        ]);

    $response->assertRedirect(route('sales-credit-notes.show', ['company' => $company->id, 'id' => $note->id]));
    $response->assertSessionHasNoErrors();

    expect($note->refresh()->status)->toBe('confirmed');
});

test('confirming burns the fiscal number within its series', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    $first = salesCreditNoteInStatus($user, $company, $client, 'draft', ['note_series' => 'A']);
    $second = salesCreditNoteInStatus($user, $company, $client, 'draft', ['note_series' => 'A']);
    /** Otra serie lleva su propia cuenta. */
    $other = salesCreditNoteInStatus($user, $company, $client, 'draft', ['note_series' => 'B']);

    foreach ([$first, $second, $other] as $note) {
        actingAs($user)->withSession(['current_company_id' => $company->id])
            ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
                'status' => 'confirmed',
            ])
            ->assertSessionHasNoErrors();
    }

    expect($first->refresh()->note_number)->toBe('00000001');
    expect($second->refresh()->note_number)->toBe('00000002');
    expect($other->refresh()->note_number)->toBe('00000001');
});

test('confirming lowers the receivable balance of the client', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    Client::where('id', $client->id)->update(['current_balance' => 500]);

    $note = salesCreditNoteInStatus($user, $company, $client, 'draft', [
        'total' => 120,
        'balance' => 120,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect((float) $client->refresh()->current_balance)->toBe(380.0);
});

test('cancelling a confirmed note gives the receivable balance back', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    Client::where('id', $client->id)->update(['current_balance' => 500]);

    $note = salesCreditNoteInStatus($user, $company, $client, 'draft', [
        'total' => 120,
        'balance' => 120,
    ]);

    foreach (['confirmed', 'cancelled'] as $status) {
        actingAs($user)->withSession(['current_company_id' => $company->id])
            ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
                'status' => $status,
            ])
            ->assertSessionHasNoErrors();
    }

    expect((float) $client->refresh()->current_balance)->toBe(500.0);
});

test('cancelling a draft note leaves the receivable balance untouched', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    Client::where('id', $client->id)->update(['current_balance' => 500]);

    $note = salesCreditNoteInStatus($user, $company, $client, 'draft', [
        'total' => 120,
        'balance' => 120,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    expect((float) $client->refresh()->current_balance)->toBe(500.0);
});

test('a confirmed note can be completed once its credit is exhausted', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    $note = salesCreditNoteInStatus($user, $company, $client, 'confirmed', [
        'total' => 100,
        'applied_amount' => 100,
        'balance' => 0,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'completed',
        ])
        ->assertSessionHasNoErrors();

    expect($note->refresh()->status)->toBe('completed');
});

test('confirming does not recalculate the frozen rate', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    $note = salesCreditNoteInStatus($user, $company, $client, 'draft', [
        'exchange_rate' => 36.5,
        'total' => 100,
        'total_ves' => 3650,
    ]);

    /** La tasa del día cambia después de emitir: la nota no la estrena. */
    \App\Modules\ExchangeRate\Models\ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 99.0]);

    app()->forgetScopedInstances();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $note->refresh();
    expect((float) $note->exchange_rate)->toBe(36.5);
    expect((float) $note->total_ves)->toBe(3650.0);
});

test('cancelling stamps the cancellation without asking for a reason', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    $note = salesCreditNoteInStatus($user, $company, $client, 'draft');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    $note->refresh();
    expect($note->status)->toBe('cancelled');
    expect($note->cancelled_at)->not->toBeNull();
});

test('a note with credit already applied cannot be cancelled', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    $note = salesCreditNoteInStatus($user, $company, $client, 'confirmed', [
        'total' => 100,
        'applied_amount' => 40,
        'balance' => 60,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasErrors('status');

    expect($note->refresh()->status)->toBe('confirmed');
});

test('a forbidden transition is rejected', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    $note = salesCreditNoteInStatus($user, $company, $client, 'draft');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'completed',
        ])
        ->assertSessionHasErrors('status');

    expect($note->refresh()->status)->toBe('draft');
});

test('a cancelled note is a dead end', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    $note = salesCreditNoteInStatus($user, $company, $client, 'cancelled');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasErrors('status');

    expect($note->refresh()->status)->toBe('cancelled');
});

test('a user without permission cannot change the status', function () {
    [$user, $company, $client] = salesCreditNoteScenario();

    $note = salesCreditNoteInStatus($user, $company, $client, 'draft');

    assignRoleWithPermissions($user, $company, ['sales-credit-notes.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'confirmed',
        ])
        ->assertForbidden();
});
