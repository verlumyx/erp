<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\Service\Models\Service;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\User\Models\User;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;

/**
 * Ejecuta un partial reload de Inertia para uno o varios grupos diferidos del
 * dashboard, resolviendo primero la versión de assets desde un render normal
 * (necesaria para que Inertia no fuerce un full reload con un 409).
 */
function dashboardPartial(User $user, Company $company, string $only): TestResponse
{
    $url = route('company.dashboard', ['company' => $company->id]);

    $html = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get($url)
        ->getContent();

    preg_match('/data-page="([^"]+)"/', (string) $html, $matches);
    $version = json_decode(html_entity_decode($matches[1]), true)['version'] ?? '';

    return actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get($url, [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) $version,
            'X-Inertia-Partial-Component' => 'dashboard',
            'X-Inertia-Partial-Data' => $only,
        ]);
}

test('the dashboard renders with deferred props left out of the initial load', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('company.dashboard', ['company' => $company->id]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->missing('metrics')
        ->missing('revenue')
        ->missing('expirations')
    );
});

test('the metrics group resolves on a partial reload scoped to the company', function () {
    [$user, $company] = createUserWithCompany();

    Transaction::factory()->income()->create(['company_id' => $company->id, 'amount' => 120, 'date' => now()->toDateString()]);
    Transaction::factory()->income()->create(['company_id' => $company->id, 'amount' => 9999, 'date' => now()->toDateString()]); // otra compañía abajo
    Transaction::factory()->income()->create(['amount' => 5000, 'date' => now()->toDateString()]);

    $response = dashboardPartial($user, $company, 'metrics');

    $response->assertOk()
        ->assertJsonPath('component', 'dashboard')
        ->assertJsonPath('props.metrics.income_month', 10119)
        ->assertJsonStructure(['props' => ['metrics' => ['net_profit', 'free_profiles', 'receivable_amount']]]);
});

test('the platforms group resolves with occupancy and platform breakdown', function () {
    [$user, $company] = createUserWithCompany();
    Service::factory()->create(['company_id' => $company->id]);

    $response = dashboardPartial($user, $company, 'occupancy,platforms');

    $response->assertOk()
        ->assertJsonPath('component', 'dashboard')
        ->assertJsonStructure(['props' => ['occupancy' => ['total'], 'platforms']]);
});

test('the company dashboard requires authentication', function () {
    $response = $this->get('/00000000-0000-0000-0000-000000000000/dashboard');

    $response->assertRedirect(route('login'));
});
