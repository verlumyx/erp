<?php

declare(strict_types=1);

use App\Modules\Account\Models\Profile;
use App\Modules\Refund\Models\Refund;
use App\Modules\Transaction\Models\Transaction;

use function Pest\Laravel\actingAs;

test('cancelling a sale with the refund checkbox creates a pending refund', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);
    $profileId = $sale->saleProfiles()->value('profile_id');

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.cancel', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'cancellation_reason' => 'Falta de pago',
            'create_refund' => true,
            'refund_amount' => 35.0,
            'refund_reason' => 'Devolución parcial',
        ]);

    $response->assertSessionHasNoErrors();

    $sale->refresh();
    expect($sale->status)->toBe('cancelled');
    expect(Profile::find($profileId)->status)->toBe('available');

    $refund = Refund::query()->where('sale_id', $sale->id)->first();
    expect($refund)->not->toBeNull();
    expect($refund->status)->toBe(Refund::STATUS_PENDING);
    expect((float) $refund->amount)->toBe(35.0);
    expect($refund->reason)->toBe('Devolución parcial');

    // Sin egreso contable hasta que se apruebe.
    expect(Transaction::query()->where('related_id', $refund->id)->count())->toBe(0);
});

test('cancelling a sale without the refund checkbox creates no refund', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);

    actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.cancel', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'cancellation_reason' => 'Falta de pago',
        ]);

    expect(Refund::query()->where('sale_id', $sale->id)->count())->toBe(0);
});

test('refund amount is required when the checkbox is checked', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.cancel', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'cancellation_reason' => 'Falta de pago',
            'create_refund' => true,
        ]);

    $response->assertSessionHasErrors('refund_amount');
});
