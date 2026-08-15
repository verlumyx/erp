<?php

declare(strict_types=1);

use App\Modules\Sale\Models\Sale;
use App\Modules\Transaction\Models\Transaction;
use Database\Seeders\SaleSeeder;

test('the sale seeder creates a varied set of sales with their transactions', function () {
    $this->seed(SaleSeeder::class);

    // 7 active + 3 in-grace + 2 out-of-grace + 3 cancelled + 3 full_account = 18.
    expect(Sale::query()->count())->toBe(18);
    expect(Sale::query()->where('status', 'active')->count())->toBeGreaterThanOrEqual(10);
    expect(Sale::query()->where('status', 'cancelled')->count())->toBe(3);
    expect(Sale::query()->where('capacity', 'full_account')->count())->toBe(3);

    // Every sale records at least its income 'sale' transaction.
    expect(Transaction::query()->where('related_type', 'Sale')->where('category', 'sale')->count())->toBe(18);
    expect(Transaction::query()->where('related_type', 'Sale')->where('category', 'renewal')->count())->toBeGreaterThan(0);
});
