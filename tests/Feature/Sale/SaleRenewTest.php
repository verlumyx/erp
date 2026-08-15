<?php

declare(strict_types=1);

use App\Modules\Sale\Models\Sale;
use App\Modules\Transaction\Models\Transaction;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('an active sale can be renewed extending its end_date', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'active', now()->addDays(5)->toDateString());
    $previousEnd = $sale->end_date->toDateString();

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.renew', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'id' => (string) Str::uuid7(),
        ]);

    $response->assertRedirect(route('sales.show', ['company' => $ctx['company']->id, 'id' => $sale->id]));
    $response->assertSessionHasNoErrors();

    $sale->refresh();
    expect($sale->status)->toBe('active');
    expect($sale->end_date->toDateString())->toBe(now()->addDays(5)->addDays(30)->toDateString());
    expect($sale->renewals()->count())->toBe(1);

    $renewal = $sale->renewals()->first();
    expect($renewal->previous_end_date->toDateString())->toBe($previousEnd);
});

test('renewing records an income transaction with renewal category', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);

    actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.renew', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'id' => (string) Str::uuid7(),
            'price' => 75,
        ]);

    $transaction = Transaction::query()
        ->where('related_type', 'Sale')
        ->where('related_id', $sale->id)
        ->where('category', 'renewal')
        ->first();

    expect($transaction)->not->toBeNull();
    expect((float) $transaction->amount)->toBe(75.00);
});

test('an expired sale within the grace period can be renewed', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'expired', now()->subDay()->toDateString());

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.renew', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'id' => (string) Str::uuid7(),
        ]);

    $response->assertSessionHasNoErrors();
    expect($sale->refresh()->status)->toBe('active');
});

test('an expired sale outside the grace period cannot be renewed', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'expired', now()->subDays(15)->toDateString());

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.renew', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'id' => (string) Str::uuid7(),
        ]);

    $response->assertSessionHasErrors('id');
    expect($sale->refresh()->status)->toBe('expired');
});

test('a cancelled sale cannot be renewed', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'cancelled', now()->subDays(2)->toDateString());

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.renew', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'id' => (string) Str::uuid7(),
        ]);

    $response->assertSessionHasErrors('id');
});

test('a user without renew permission cannot renew', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);
    assignRoleWithPermissions($ctx['user'], $ctx['company'], ['sales.list']);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.renew', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'id' => (string) Str::uuid7(),
        ]);

    $response->assertForbidden();
});
