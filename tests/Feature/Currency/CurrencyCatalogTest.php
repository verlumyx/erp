<?php

declare(strict_types=1);

use App\Modules\Currency\Models\Currency;
use App\Modules\Currency\Repositories\Contracts\CurrencyRepositoryInterface;
use App\Modules\Currency\Services\CurrencyOptionsService;
use Database\Seeders\CurrencySeeder;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\actingAs;

test('the seeder creates the three system currencies', function () {
    $codes = Currency::query()->orderBy('order')->pluck('code')->all();

    expect($codes)->toBe(['USD', 'EUR', 'VES']);

    $bolivar = Currency::query()->where('code', 'VES')->firstOrFail();

    expect($bolivar->name)->toBe('Bolívares')
        ->and($bolivar->symbol)->toBe('Bs.')
        ->and($bolivar->status)->toBe('active');
});

test('seeding twice does not duplicate the catalog', function () {
    $this->seed(CurrencySeeder::class);

    expect(Currency::query()->count())->toBe(3);
});

test('the catalog is global: it has no company column', function () {
    expect(Schema::hasColumn('app_currencies', 'company_id'))->toBeFalse();
    expect(Schema::hasColumn('app_currencies', 'code'))->toBeTrue();
});

test('the currency code is unique across the system', function () {
    Currency::factory()->create(['code' => 'USD']);
})->throws(Illuminate\Database\QueryException::class);

test('only active currencies are offered as options', function () {
    Currency::query()->where('code', 'EUR')->update(['status' => 'inactive']);

    $options = app(CurrencyOptionsService::class)->execute();

    expect(collect($options)->pluck('code')->all())->toBe(['USD', 'VES'])
        ->and($options[0])->toBe(['code' => 'USD', 'name' => 'Dólar estadounidense', 'symbol' => '$']);
});

test('the repository exposes the active codes used by the validation rule', function () {
    expect(app(CurrencyRepositoryInterface::class)->activeCodes())
        ->toBe(['USD', 'EUR', 'VES']);
});

test('the currency catalog is shared with every inertia page', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('company.dashboard', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(function ($page) {
        $currencies = collect($page->toArray()['props']['currencies']);

        expect($currencies->pluck('code')->all())->toBe(['USD', 'EUR', 'VES'])
            ->and($currencies->firstWhere('code', 'VES')['symbol'])->toBe('Bs.');
    });
});

test('the same catalog is shared regardless of the company', function () {
    [$user, $company] = createUserWithCompany();
    [$otherUser, $otherCompany] = createUserWithCompany();

    foreach ([[$user, $company], [$otherUser, $otherCompany]] as [$currentUser, $currentCompany]) {
        $response = actingAs($currentUser)
            ->withSession(['current_company_id' => $currentCompany->id])
            ->get(route('company.dashboard', ['company' => $currentCompany->id]));

        $response->assertInertia(function ($page) {
            expect(collect($page->toArray()['props']['currencies'])->pluck('code')->all())
                ->toBe(['USD', 'EUR', 'VES']);
        });
    }
});
