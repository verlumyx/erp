<?php

declare(strict_types=1);

use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;

use function Pest\Laravel\actingAs;

/** Una nota de la empresa activa en el estado que pida el test. */
function purchaseCreditNoteInStatus(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    string $status,
    array $overrides = [],
): PurchaseCreditNote {
    return PurchaseCreditNote::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'created_by' => $user->id,
        'status' => $status,
        ...$overrides,
    ]);
}

test('a draft note can be confirmed', function () {
    [$user, $company, $supplier] = purchaseCreditNoteScenario();

    $note = purchaseCreditNoteInStatus($user, $company, $supplier, 'draft');

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'confirmed',
        ]);

    $response->assertRedirect(route('purchase-credit-notes.show', ['company' => $company->id, 'id' => $note->id]));
    $response->assertSessionHasNoErrors();

    expect($note->refresh()->status)->toBe('confirmed');
});

test('a confirmed note can be completed once its credit is exhausted', function () {
    [$user, $company, $supplier] = purchaseCreditNoteScenario();

    $note = purchaseCreditNoteInStatus($user, $company, $supplier, 'confirmed', [
        'total' => 100,
        'applied_amount' => 100,
        'balance' => 0,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'completed',
        ])
        ->assertSessionHasNoErrors();

    expect($note->refresh()->status)->toBe('completed');
});

test('confirming does not recalculate the frozen rate', function () {
    [$user, $company, $supplier] = purchaseCreditNoteScenario();

    $note = purchaseCreditNoteInStatus($user, $company, $supplier, 'draft', [
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
        ->put(route('purchase-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $note->refresh();
    expect((float) $note->exchange_rate)->toBe(36.5);
    expect((float) $note->total_ves)->toBe(3650.0);
});

test('cancelling stamps the cancellation without asking for a reason', function () {
    [$user, $company, $supplier] = purchaseCreditNoteScenario();

    $note = purchaseCreditNoteInStatus($user, $company, $supplier, 'draft');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    $note->refresh();
    expect($note->status)->toBe('cancelled');
    expect($note->cancelled_at)->not->toBeNull();
});

test('a note with credit already applied cannot be cancelled', function () {
    [$user, $company, $supplier] = purchaseCreditNoteScenario();

    $note = purchaseCreditNoteInStatus($user, $company, $supplier, 'confirmed', [
        'total' => 100,
        'applied_amount' => 40,
        'balance' => 60,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasErrors('status');

    expect($note->refresh()->status)->toBe('confirmed');
});

test('a forbidden transition is rejected', function () {
    [$user, $company, $supplier] = purchaseCreditNoteScenario();

    $note = purchaseCreditNoteInStatus($user, $company, $supplier, 'draft');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'completed',
        ])
        ->assertSessionHasErrors('status');

    expect($note->refresh()->status)->toBe('draft');
});

test('a cancelled note is a dead end', function () {
    [$user, $company, $supplier] = purchaseCreditNoteScenario();

    $note = purchaseCreditNoteInStatus($user, $company, $supplier, 'cancelled');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasErrors('status');

    expect($note->refresh()->status)->toBe('cancelled');
});

test('a user without permission cannot change the status', function () {
    [$user, $company, $supplier] = purchaseCreditNoteScenario();

    $note = purchaseCreditNoteInStatus($user, $company, $supplier, 'draft');

    assignRoleWithPermissions($user, $company, ['purchase-credit-notes.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'confirmed',
        ])
        ->assertForbidden();
});
