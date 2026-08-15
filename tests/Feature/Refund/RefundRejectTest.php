<?php

declare(strict_types=1);

use App\Modules\Refund\Models\Refund;
use App\Modules\Sale\Models\Sale;
use App\Modules\Transaction\Models\Transaction;

use function Pest\Laravel\actingAs;

test('rejecting a refund leaves the sale untouched and records no transaction', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);
    $refund = persistPendingRefund($ctx, $sale, 40.0);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('refunds.reject', ['company' => $ctx['company']->id, 'id' => $refund->id]));

    $response->assertSessionHasNoErrors();

    $refund->refresh();
    expect($refund->status)->toBe(Refund::STATUS_REJECTED);
    expect($refund->resolved_by)->toBe($ctx['user']->id);

    $sale->refresh();
    expect($sale->status)->toBe(Sale::STATUS_ACTIVE);

    expect(Transaction::query()->where('related_id', $refund->id)->count())->toBe(0);
});

test('an already resolved refund cannot be rejected again', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);
    $refund = Refund::factory()->forSale($sale)->rejected()->create([
        'requested_by' => $ctx['user']->id,
    ]);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('refunds.reject', ['company' => $ctx['company']->id, 'id' => $refund->id]));

    $response->assertConflict();
});

test('a user without reject permission cannot reject a refund', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);
    $refund = persistPendingRefund($ctx, $sale);
    assignRoleWithPermissions($ctx['user'], $ctx['company'], ['refunds.list']);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('refunds.reject', ['company' => $ctx['company']->id, 'id' => $refund->id]));

    $response->assertForbidden();
});
