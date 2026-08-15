<?php

declare(strict_types=1);

use App\Modules\Refund\Models\Refund;
use App\Modules\Transaction\Models\Transaction;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a refund can be created manually as pending without an accounting transaction', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('refunds.store', ['company' => $ctx['company']->id]), [
            'id' => (string) Str::uuid7(),
            'sale_id' => $sale->id,
            'amount' => 30.50,
            'reason' => 'Cliente insatisfecho',
        ]);

    $response->assertSessionHasNoErrors();

    $refund = Refund::query()->where('sale_id', $sale->id)->first();
    expect($refund)->not->toBeNull();
    expect($refund->status)->toBe(Refund::STATUS_PENDING);
    expect($refund->code)->toBe('REF000001');
    expect($refund->client_id)->toBe($sale->client_id);
    expect((float) $refund->amount)->toBe(30.50);

    $response->assertRedirect(route('refunds.show', [
        'company' => $ctx['company']->id,
        'id' => $refund->id,
    ]));

    expect(Transaction::query()->where('related_id', $refund->id)->count())->toBe(0);
});

test('the refund code is sequential per company', function () {
    $ctx = makeSaleContext();
    $saleOne = persistSale($ctx);
    $saleTwo = persistSale($ctx);

    foreach ([$saleOne, $saleTwo] as $sale) {
        actingAs($ctx['user'])
            ->withSession(['current_company_id' => $ctx['company']->id])
            ->post(route('refunds.store', ['company' => $ctx['company']->id]), [
                'id' => (string) Str::uuid7(),
                'sale_id' => $sale->id,
                'amount' => 10,
            ]);
    }

    expect(Refund::query()->pluck('code')->sort()->values()->all())
        ->toBe(['REF000001', 'REF000002']);
});

test('amount is required and must be positive', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('refunds.store', ['company' => $ctx['company']->id]), [
            'id' => (string) Str::uuid7(),
            'sale_id' => $sale->id,
            'amount' => 0,
        ]);

    $response->assertSessionHasErrors('amount');
});

test('a user without create permission cannot create a refund', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);
    assignRoleWithPermissions($ctx['user'], $ctx['company'], ['refunds.list']);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('refunds.store', ['company' => $ctx['company']->id]), [
            'id' => (string) Str::uuid7(),
            'sale_id' => $sale->id,
            'amount' => 10,
        ]);

    $response->assertForbidden();
});
