<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\Company\Models\Company;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list only shows the advances of the active company', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    createClientAdvance($user, $company, $client);

    $otherCompany = Company::create([
        'name' => 'Otra empresa',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    ClientAdvance::factory()->create([
        'company_id' => $otherCompany->id,
        'client_id' => Client::factory()->create(['company_id' => $otherCompany->id])->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-advances.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client-advances/index')
            ->has('clientAdvances', 1)
            ->where('meta.total', 1));
});

test('the list filters by client, status and payment method', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $other = Client::factory()->create(['company_id' => $company->id]);

    createClientAdvance($user, $company, $client, ['payment_method' => 'transfer']);
    createClientAdvance($user, $company, $other, ['payment_method' => 'cash']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-advances.index', [
            'company' => $company->id,
            'client_id' => $client->id,
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('clientAdvances', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-advances.index', [
            'company' => $company->id,
            'payment_method' => 'cash',
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('clientAdvances', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-advances.index', [
            'company' => $company->id,
            'status' => 'confirmed',
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('clientAdvances', 0));
});

test('the list filters by date range', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    /** Un anticipo con fecha vieja se valora con la tasa de su día, no con la de hoy. */
    ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => now()->subDays(10)->toDateString(),
        'rate' => 35.0,
        'type' => 'legal',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    createClientAdvance($user, $company, $client, [
        'advance_date' => now()->subDays(10)->toDateString(),
    ]);
    createClientAdvance($user, $company, $client);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-advances.index', [
            'company' => $company->id,
            'date_from' => now()->subDay()->toDateString(),
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('clientAdvances', 1));
});

test('the lookup only offers the advances that still have credit', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    /** En borrador todavía no hay crédito que aplicar. */
    createClientAdvance($user, $company, $client);

    $received = confirmedClientAdvance($user, $company, $client, ['amount' => 400]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('client-advances.lookup', ['company' => $company->id, 'open' => 'yes']));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($received->id);
    expect((float) $response->json('data.0.meta.balance'))->toBe(400.0);
});

test('a user without permission cannot list the advances', function () {
    [$user, $company] = clientAdvanceScenario();

    assignRoleWithPermissions($user, $company, ['clients.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-advances.index', ['company' => $company->id]))
        ->assertForbidden();
});
