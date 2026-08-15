<?php

declare(strict_types=1);

use App\Modules\Account\Models\Account;
use App\Modules\Service\Models\Service;
use App\Modules\Transaction\Commands\SearchTransactionCommand;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Services\TransactionSearchService;

function searchTransactions(array $filters = [], ?string $companyId = null, int $limit = 20, int $offset = 0): array
{
    return app(TransactionSearchService::class)->execute(
        new SearchTransactionCommand(
            filters: $filters,
            limit: $limit,
            offset: $offset,
            companyId: $companyId,
        )
    );
}

test('search filters by type', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->count(3)->income()->create(['company_id' => $company->id, 'recorded_by' => $user->id]);
    Transaction::factory()->count(2)->expense()->create(['company_id' => $company->id, 'recorded_by' => $user->id]);

    $result = searchTransactions(['type' => 'income'], $company->id);

    expect($result['total'])->toBe(3);
    expect($result['data'])->toHaveCount(3);
    expect(collect($result['data'])->pluck('type')->unique()->all())->toBe(['income']);
});

test('search filters by category', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->create(['company_id' => $company->id, 'recorded_by' => $user->id, 'type' => 'expense', 'category' => 'salary']);
    Transaction::factory()->create(['company_id' => $company->id, 'recorded_by' => $user->id, 'type' => 'expense', 'category' => 'marketing']);

    $result = searchTransactions(['category' => 'salary'], $company->id);

    expect($result['total'])->toBe(1);
    expect($result['data'][0]->category)->toBe('salary');
});

test('search filters by date range', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->create(['company_id' => $company->id, 'recorded_by' => $user->id, 'date' => '2026-01-10']);
    Transaction::factory()->create(['company_id' => $company->id, 'recorded_by' => $user->id, 'date' => '2026-03-15']);
    Transaction::factory()->create(['company_id' => $company->id, 'recorded_by' => $user->id, 'date' => '2026-05-20']);

    $result = searchTransactions(['date_from' => '2026-02-01', 'date_to' => '2026-04-01'], $company->id);

    expect($result['total'])->toBe(1);
    expect($result['data'][0]->date->toDateString())->toBe('2026-03-15');
});

test('search filters by related Account', function () {
    [$user, $company] = createUserWithCompany();

    $service = Service::factory()->create(['company_id' => $company->id]);
    $account = Account::factory()->create(['company_id' => $company->id, 'service_id' => $service->id]);

    Transaction::factory()->count(2)->forAccount($account)->create(['recorded_by' => $user->id]);
    Transaction::factory()->expense()->create(['company_id' => $company->id, 'recorded_by' => $user->id]);

    $result = searchTransactions([
        'related_type' => 'Account',
        'related_id' => $account->id,
    ], $company->id);

    expect($result['total'])->toBe(2);
    expect(collect($result['data'])->pluck('related_id')->unique()->all())->toBe([$account->id]);
});

test('search is scoped to the given company', function () {
    [$userA, $companyA] = createUserWithCompany();
    [$userB, $companyB] = createUserWithCompany();

    Transaction::factory()->count(2)->create(['company_id' => $companyA->id, 'recorded_by' => $userA->id]);
    Transaction::factory()->count(3)->create(['company_id' => $companyB->id, 'recorded_by' => $userB->id]);

    $result = searchTransactions([], $companyA->id);

    expect($result['total'])->toBe(2);
    expect(collect($result['data'])->pluck('company_id')->unique()->all())->toBe([$companyA->id]);
});

test('search paginates with limit and offset', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->count(5)->create(['company_id' => $company->id, 'recorded_by' => $user->id]);

    $result = searchTransactions([], $company->id, limit: 2, offset: 0);

    expect($result['total'])->toBe(5);
    expect($result['data'])->toHaveCount(2);
});
