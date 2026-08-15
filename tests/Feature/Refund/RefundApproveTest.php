<?php

declare(strict_types=1);

use App\Modules\Account\Models\Profile;
use App\Modules\Refund\Models\Refund;
use App\Modules\Sale\Models\Sale;
use App\Modules\Transaction\Models\Transaction;

use function Pest\Laravel\actingAs;

/**
 * Crea un reembolso pendiente persistido para una venta del contexto.
 */
function persistPendingRefund(array $ctx, Sale $sale, float $amount = 50.0): Refund
{
    return Refund::factory()->forSale($sale)->pending()->create([
        'requested_by' => $ctx['user']->id,
        'amount' => $amount,
    ]);
}

test('approving a refund for an active sale cancels it, frees profiles and records an expense', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);
    $profileId = $sale->saleProfiles()->value('profile_id');
    $refund = persistPendingRefund($ctx, $sale, 40.0);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('refunds.approve', ['company' => $ctx['company']->id, 'id' => $refund->id]));

    $response->assertRedirect(route('refunds.show', ['company' => $ctx['company']->id, 'id' => $refund->id]));
    $response->assertSessionHasNoErrors();

    $sale->refresh();
    expect($sale->status)->toBe(Sale::STATUS_CANCELLED);
    expect(Profile::find($profileId)->status)->toBe('available');

    $refund->refresh();
    expect($refund->status)->toBe(Refund::STATUS_APPROVED);
    expect($refund->resolved_by)->toBe($ctx['user']->id);
    expect($refund->resolved_at)->not->toBeNull();

    $transaction = Transaction::query()
        ->where('related_type', 'Refund')
        ->where('related_id', $refund->id)
        ->first();

    expect($transaction)->not->toBeNull();
    expect($transaction->type)->toBe(Transaction::TYPE_EXPENSE);
    expect($transaction->category)->toBe('refund');
    expect((float) $transaction->amount)->toBe(40.0);
});

test('approving a refund whose sale is already cancelled only records the expense', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, Sale::STATUS_CANCELLED);
    $refund = persistPendingRefund($ctx, $sale, 25.0);

    actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('refunds.approve', ['company' => $ctx['company']->id, 'id' => $refund->id]));

    $sale->refresh();
    expect($sale->status)->toBe(Sale::STATUS_CANCELLED);

    expect(Transaction::query()
        ->where('related_type', 'Refund')
        ->where('related_id', $refund->id)
        ->count())->toBe(1);

    expect($refund->fresh()->status)->toBe(Refund::STATUS_APPROVED);
});

test('an already resolved refund cannot be approved again', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);
    $refund = Refund::factory()->forSale($sale)->approved()->create([
        'requested_by' => $ctx['user']->id,
    ]);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('refunds.approve', ['company' => $ctx['company']->id, 'id' => $refund->id]));

    $response->assertConflict();
});

test('a user without approve permission cannot approve a refund', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);
    $refund = persistPendingRefund($ctx, $sale);
    assignRoleWithPermissions($ctx['user'], $ctx['company'], ['refunds.list']);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('refunds.approve', ['company' => $ctx['company']->id, 'id' => $refund->id]));

    $response->assertForbidden();
});
