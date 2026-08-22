<?php

declare(strict_types=1);

use App\Modules\Configuration\Models\Configuration;
use App\Modules\ExchangeRate\Models\ExchangeRate;

use function Pest\Laravel\actingAs;

/**
 * Las tasas de hoy viajan en las props compartidas para que una pantalla pueda
 * mostrar el equivalente de un importe que todavía se está capturando. Nunca
 * bloquean: una moneda sin tasa simplemente no aparece.
 */
function sharedRates(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
): \Illuminate\Testing\TestResponse {
    return actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('configuration.edit', ['company' => $company->id]));
}

test('the shared rates carry the rate of the day of each currency', function () {
    [$user, $company] = createUserWithCompany();

    todayExchangeRate($company, $user);

    sharedRates($user, $company)->assertInertia(fn ($page) => $page
        ->where('todayRates.USD', 36.5)
        /** El bolívar vale 1 y nunca lleva tasa cargada. */
        ->where('todayRates.VES', 1)
        ->missing('todayRates.EUR')
    );
});

test('the shared rates fall back to the last rate published', function () {
    [$user, $company] = createUserWithCompany();

    ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => now()->subDays(3)->toDateString(),
        'rate' => 35.25,
        'type' => 'legal',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    sharedRates($user, $company)->assertInertia(fn ($page) => $page
        ->where('todayRates.USD', 35.25)
    );
});

test('the shared rates follow the series the company works with', function () {
    [$user, $company] = createUserWithCompany();

    Configuration::query()
        ->where('company_id', $company->id)
        ->update(['rate_type' => 'manual']);

    todayExchangeRate($company, $user);

    /** La serie legal no alimenta a una empresa que valora con la manual. */
    sharedRates($user, $company)->assertInertia(fn ($page) => $page
        ->missing('todayRates.USD')
    );
});
