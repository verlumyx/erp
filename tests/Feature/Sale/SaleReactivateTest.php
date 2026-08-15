<?php

declare(strict_types=1);

use App\Modules\Account\Models\Profile;
use App\Modules\Transaction\Models\Transaction;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a cancelled sale is reactivated reusing its original profiles when free', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'cancelled', now()->subDays(2)->toDateString());
    $profileId = $sale->saleProfiles()->value('profile_id');

    // Tras la cancelación el profile quedó libre.
    Profile::where('id', $profileId)->update(['status' => 'available']);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.reactivate', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'id' => (string) Str::uuid7(),
        ]);

    $response->assertRedirect(route('sales.show', ['company' => $ctx['company']->id, 'id' => $sale->id]));
    $response->assertSessionHasNoErrors();

    $sale->refresh();
    expect($sale->status)->toBe('active');
    expect($sale->cancelled_at)->toBeNull();
    expect(Profile::find($profileId)->status)->toBe('occupied');
    expect($sale->renewals()->count())->toBe(1);

    $transaction = Transaction::query()
        ->where('related_id', $sale->id)
        ->where('category', 'renewal')
        ->first();
    expect($transaction)->not->toBeNull();
});

test('reactivating with a new price updates the sale price and snapshot', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'cancelled', now()->subDays(2)->toDateString());
    $profileId = $sale->saleProfiles()->value('profile_id');
    \App\Modules\Account\Models\Profile::where('id', $profileId)->update(['status' => 'available']);

    actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.reactivate', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'id' => (string) Str::uuid7(),
            'price' => 120,
            'duration_days' => 45,
        ]);

    $sale->refresh();
    expect((float) $sale->price)->toBe(120.00);
    expect((int) $sale->duration_days)->toBe(45);

    $renewal = $sale->renewals()->first();
    expect((float) $renewal->price)->toBe(120.00);

    $transaction = Transaction::query()
        ->where('related_id', $sale->id)
        ->where('category', 'renewal')
        ->first();
    expect((float) $transaction->amount)->toBe(120.00);
});

test('reactivation returns 409 when original profiles are taken and no replacements given', function () {
    $ctx = makeSaleContext();
    // El profile original sigue ocupado (lo tomó otra venta).
    $sale = persistSale($ctx, 'cancelled', now()->subDays(2)->toDateString());

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->postJson(route('sales.reactivate', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'id' => (string) Str::uuid7(),
        ]);

    $response->assertStatus(409);
    $response->assertJsonStructure(['message', 'unavailable_profiles']);
    expect($sale->refresh()->status)->toBe('cancelled');
});

test('reactivation succeeds when replacement profiles are provided', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'cancelled', now()->subDays(2)->toDateString());

    // El original sigue ocupado; se elige otro profile disponible del mismo servicio.
    $replacement = $ctx['profiles']->get(1);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.reactivate', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'id' => (string) Str::uuid7(),
            'profile_ids' => [$replacement->id],
        ]);

    $response->assertSessionHasNoErrors();
    $sale->refresh();
    expect($sale->status)->toBe('active');
    expect(Profile::find($replacement->id)->status)->toBe('occupied');
    expect($sale->saleProfiles()->pluck('profile_id')->all())->toBe([$replacement->id]);
});

test('an expired sale outside the grace period can be reactivated', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'expired', now()->subDays(15)->toDateString());
    $profileId = $sale->saleProfiles()->value('profile_id');

    // El job de expiración ya liberó el profile.
    Profile::where('id', $profileId)->update(['status' => 'available']);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.reactivate', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'id' => (string) Str::uuid7(),
        ]);

    $response->assertSessionHasNoErrors();
    expect($sale->refresh()->status)->toBe('active');
});

test('an active sale cannot be reactivated', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'active');

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.reactivate', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'id' => (string) Str::uuid7(),
        ]);

    $response->assertSessionHasErrors('id');
});

test('a user without reactivate permission cannot reactivate', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'cancelled', now()->subDays(2)->toDateString());
    assignRoleWithPermissions($ctx['user'], $ctx['company'], ['sales.list']);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.reactivate', ['company' => $ctx['company']->id, 'id' => $sale->id]), [
            'id' => (string) Str::uuid7(),
        ]);

    $response->assertForbidden();
});
