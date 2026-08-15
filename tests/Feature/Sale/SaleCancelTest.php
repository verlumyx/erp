<?php

declare(strict_types=1);

use App\Modules\Account\Models\Profile;
use App\Modules\Transaction\Models\Transaction;

use function Pest\Laravel\actingAs;

test('cancelling a sale marks it cancelled and frees its profiles', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);
    $profileId = $sale->saleProfiles()->value('profile_id');

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.cancel', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'cancellation_reason' => 'Falta de pago',
        ]);

    $response->assertRedirect(route('sales.show', ['company' => $ctx['company']->id, 'id' => $sale->id]));
    $response->assertSessionHasNoErrors();

    $sale->refresh();
    expect($sale->status)->toBe('cancelled');
    expect($sale->cancelled_at)->not->toBeNull();
    expect($sale->cancellation_reason)->toBe('Falta de pago');
    expect(Profile::find($profileId)->status)->toBe('available');
});

test('cancelling a sale does not create a negative transaction', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);

    actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.cancel', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'cancellation_reason' => 'Falta de pago',
        ]);

    expect(Transaction::query()->where('related_id', $sale->id)->count())->toBe(0);
});

test('cancellation reason is required', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.cancel', ['company' => $ctx['company']->id, 'id' => $sale->id]), []);

    $response->assertSessionHasErrors('cancellation_reason');
});

test('a user without cancel permission cannot cancel', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);
    assignRoleWithPermissions($ctx['user'], $ctx['company'], ['sales.list']);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.cancel', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'cancellation_reason' => 'x',
        ]);

    $response->assertForbidden();
});
