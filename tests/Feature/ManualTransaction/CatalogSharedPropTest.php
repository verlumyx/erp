<?php

declare(strict_types=1);

use App\Modules\Transaction\Models\Transaction;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('the transaction catalog is shared with every Inertia page', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('manual-transactions.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('transactionCatalog.types', 2)
            ->has('transactionCatalog.categories', 14)
            ->has('transactionCatalog.income', count(Transaction::INCOME_CATEGORIES))
            ->has('transactionCatalog.expense', count(Transaction::EXPENSE_CATEGORIES))
        );
});

test('every catalog category type matches the backend constants', function () {
    $catalog = Transaction::catalog();

    foreach ($catalog['categories'] as $category) {
        expect($category['type'])->toBe(Transaction::typeForCategory($category['value']));
    }
});
