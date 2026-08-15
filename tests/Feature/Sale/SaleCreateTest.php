<?php

declare(strict_types=1);

use App\Modules\Account\Models\Account;
use App\Modules\Account\Models\Profile;
use App\Modules\Plan\Models\Plan;
use App\Modules\Sale\Models\Sale;
use App\Modules\Transaction\Models\Transaction;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

function salePayload(array $context, array $overrides = []): array
{
    return array_merge([
        'id' => (string) Str::uuid7(),
        'client_id' => $context['client']->id,
        'plan_id' => $context['plan']->id,
        'start_date' => now()->toDateString(),
        'profile_ids' => [$context['profiles']->first()->id],
    ], $overrides);
}

test('a sale is created with the plan snapshot and computed end_date', function () {
    $ctx = makeSaleContext();
    $id = (string) Str::uuid7();

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.store', ['company' => $ctx['company']->id]), salePayload($ctx, ['id' => $id]));

    $response->assertRedirect(route('sales.show', ['company' => $ctx['company']->id, 'id' => $id]));
    $response->assertSessionHasNoErrors();

    $sale = Sale::find($id);
    expect($sale)->not->toBeNull();
    expect($sale->code)->toBe('SAL000001');
    expect($sale->capacity)->toBe('profile');
    expect((int) $sale->duration_days)->toBe(30);
    expect((float) $sale->price)->toBe(50.00);
    expect($sale->service_id)->toBe($ctx['service']->id);
    expect($sale->end_date->toDateString())->toBe(now()->addDays(30)->toDateString());
    expect($sale->status)->toBe('active');
});

test('the snapshot is immutable when the plan changes afterwards', function () {
    $ctx = makeSaleContext();
    $id = (string) Str::uuid7();

    actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.store', ['company' => $ctx['company']->id]), salePayload($ctx, ['id' => $id]));

    $ctx['plan']->update(['sale_price' => 999, 'duration_days' => 365]);

    $sale = Sale::find($id);
    expect((float) $sale->price)->toBe(50.00);
    expect((int) $sale->duration_days)->toBe(30);
});

test('creating a sale occupies the assigned profile', function () {
    $ctx = makeSaleContext();
    $profile = $ctx['profiles']->first();

    actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.store', ['company' => $ctx['company']->id]), salePayload($ctx));

    expect(Profile::find($profile->id)->status)->toBe('occupied');
});

test('creating a sale records an income transaction related to the sale', function () {
    $ctx = makeSaleContext();
    $id = (string) Str::uuid7();

    actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.store', ['company' => $ctx['company']->id]), salePayload($ctx, ['id' => $id]));

    $transaction = Transaction::query()
        ->where('related_type', 'Sale')
        ->where('related_id', $id)
        ->first();

    expect($transaction)->not->toBeNull();
    expect($transaction->type)->toBe('income');
    expect($transaction->category)->toBe('sale');
    expect((float) $transaction->amount)->toBe(50.00);
});

test('a full_account sale requires exactly max_profiles profiles from the same account', function () {
    $ctx = makeSaleContext(maxProfiles: 4);

    $plan = Plan::factory()->create([
        'company_id' => $ctx['company']->id,
        'service_id' => $ctx['service']->id,
        'capacity' => 'full_account',
        'duration_days' => 30,
        'sale_price' => 80,
    ]);

    $id = (string) Str::uuid7();

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.store', ['company' => $ctx['company']->id]), [
            'id' => $id,
            'client_id' => $ctx['client']->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'profile_ids' => $ctx['profiles']->pluck('id')->all(),
        ]);

    $response->assertSessionHasNoErrors();
    expect(Sale::find($id)->saleProfiles()->count())->toBe(4);
    expect(Profile::whereIn('id', $ctx['profiles']->pluck('id'))->where('status', 'occupied')->count())->toBe(4);
});

test('a profile capacity plan rejects more than one profile', function () {
    $ctx = makeSaleContext();

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.store', ['company' => $ctx['company']->id]), salePayload($ctx, [
            'profile_ids' => $ctx['profiles']->take(2)->pluck('id')->all(),
        ]));

    $response->assertSessionHasErrors('profile_ids');
});

test('a sale cannot be created for an inactive client', function () {
    $ctx = makeSaleContext();
    $ctx['client']->update(['status' => 'inactive']);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.store', ['company' => $ctx['company']->id]), salePayload($ctx));

    $response->assertSessionHasErrors('client_id');
});

test('a sale rejects an occupied profile', function () {
    $ctx = makeSaleContext();
    $ctx['profiles']->first()->update(['status' => 'occupied']);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.store', ['company' => $ctx['company']->id]), salePayload($ctx));

    $response->assertSessionHasErrors('profile_ids');
});

test('a sale rejects a profile from a different service', function () {
    $ctx = makeSaleContext();

    $otherService = App\Modules\Service\Models\Service::factory()->create([
        'company_id' => $ctx['company']->id,
        'max_profiles' => 2,
    ]);
    $otherAccount = Account::factory()->create([
        'company_id' => $ctx['company']->id,
        'service_id' => $otherService->id,
    ]);
    $otherProfile = Profile::factory()->create([
        'account_id' => $otherAccount->id,
        'number' => 1,
        'status' => 'available',
    ]);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.store', ['company' => $ctx['company']->id]), salePayload($ctx, [
            'profile_ids' => [$otherProfile->id],
        ]));

    $response->assertSessionHasErrors('profile_ids');
});

test('a user without permission cannot create a sale', function () {
    $ctx = makeSaleContext();
    assignRoleWithPermissions($ctx['user'], $ctx['company'], ['sales.list']);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->post(route('sales.store', ['company' => $ctx['company']->id]), salePayload($ctx));

    $response->assertForbidden();
});
