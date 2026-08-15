<?php

declare(strict_types=1);

use App\Modules\Client\Exceptions\ClientNotFoundException;
use App\Modules\Client\Models\Client;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('the client show page renders', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create(['company_id' => $company->id, 'name' => 'Visible Client']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('clients.show', ['company' => $company->id, 'id' => $client->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('clients/show')
        ->where('client.id', $client->id)
        ->where('client.name', 'Visible Client')
    );
});

test('the client show page includes the client real sales with their profiles', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->get(route('clients.show', ['company' => $ctx['company']->id, 'id' => $ctx['client']->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('clients/show')
        ->has('sales', 1)
        ->where('sales.0.id', $sale->id)
        ->where('sales.0.status', 'active')
        ->where('sales.0.service.id', $ctx['service']->id)
        ->has('sales.0.sale_profiles', 1)
    );
});

test('the client show page exposes real metrics computed from the client sales', function () {
    $ctx = makeSaleContext();
    persistSale($ctx);                    // activa, price 50.00
    persistSale($ctx, status: 'expired'); // expirada, price 50.00

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->get(route('clients.show', ['company' => $ctx['company']->id, 'id' => $ctx['client']->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('clients/show')
        ->where('metrics.monthly_income', fn ($value): bool => (float) $value === 50.0)
        ->where('metrics.pending_debt', fn ($value): bool => (float) $value === 50.0)
        ->where('metrics.total_paid', fn ($value): bool => (float) $value === 100.0)
    );
});

test('the client show page excludes cancelled sales from the sales prop', function () {
    $ctx = makeSaleContext();
    persistSale($ctx, status: 'cancelled');

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->get(route('clients.show', ['company' => $ctx['company']->id, 'id' => $ctx['client']->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('clients/show')
        ->has('sales', 0)
    );
});

test('the client edit page renders', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('clients.edit', ['company' => $company->id, 'id' => $client->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('clients/edit')
        ->where('client.id', $client->id)
    );
});

test('showing a missing client throws a not found exception', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('clients.show', ['company' => $company->id, 'id' => (string) Str::uuid7()]));
})->throws(ClientNotFoundException::class);
