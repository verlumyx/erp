<?php

declare(strict_types=1);

use App\Modules\Account\Models\Profile;
use App\Modules\Sale\Models\Sale;

use function Pest\Laravel\artisan;

test('sales:expire marks due active sales as expired without freeing profiles', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'active', now()->subDay()->toDateString());
    $profileId = $sale->saleProfiles()->value('profile_id');

    artisan('sales:expire')->assertExitCode(0);

    expect($sale->refresh()->status)->toBe('expired');
    expect(Profile::find($profileId)->status)->toBe('occupied');
});

test('sales:expire leaves active sales that are not yet due', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'active', now()->addDays(5)->toDateString());

    artisan('sales:expire')->assertExitCode(0);

    expect($sale->refresh()->status)->toBe('active');
});

test('sales:expire frees profiles of sales expired beyond the grace period', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'expired', now()->subDays(15)->toDateString());
    $profileId = $sale->saleProfiles()->value('profile_id');

    artisan('sales:expire')->assertExitCode(0);

    expect($sale->refresh()->status)->toBe('expired');
    expect(Profile::find($profileId)->status)->toBe('available');
});

test('sales:expire keeps profiles occupied while sale is still within grace', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'expired', now()->subDay()->toDateString());
    $profileId = $sale->saleProfiles()->value('profile_id');

    artisan('sales:expire')->assertExitCode(0);

    expect(Profile::find($profileId)->status)->toBe('occupied');
});
