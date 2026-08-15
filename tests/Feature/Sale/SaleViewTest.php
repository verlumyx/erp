<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

test('the sales index renders with the company sales', function () {
    $ctx = makeSaleContext();
    persistSale($ctx, 'active');

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->get(route('sales.index', ['company' => $ctx['company']->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('sales/index')
        ->has('sales', 1)
        ->where('meta.total', 1)
    );
});

test('sales can be filtered by status', function () {
    $ctx = makeSaleContext(maxProfiles: 6);
    persistSale($ctx, 'active');
    persistSale($ctx, 'cancelled', now()->subDays(2)->toDateString());

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->get(route('sales.index', ['company' => $ctx['company']->id, 'status' => 'cancelled']));

    $response->assertInertia(fn ($page) => $page->has('sales', 1));
});

test('the sales index exposes the current plan price and duration for each row', function () {
    $ctx = makeSaleContext();
    persistSale($ctx, 'active');

    $ctx['plan']->update([
        'duration_days' => 60,
        'sale_price' => 75.00,
    ]);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->get(route('sales.index', ['company' => $ctx['company']->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('sales.0.plan.duration_days', 60)
        ->where('sales.0.plan.sale_price', '75.00')
        ->where('sales.0.duration_days', 30)
        ->where('sales.0.price', '50.00')
    );
});

test('the create page exposes clients, plans and available profiles', function () {
    $ctx = makeSaleContext();

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->get(route('sales.create', ['company' => $ctx['company']->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('sales/create')
        ->has('clients')
        ->has('plans')
        ->has('availableProfiles')
    );
});

test('the create page preselects an active client passed via the client query param', function () {
    $ctx = makeSaleContext();

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->get(route('sales.create', [
            'company' => $ctx['company']->id,
            'client' => $ctx['client']->id,
        ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('sales/create')
        ->where('preselectedClientId', $ctx['client']->id)
        ->where('clients', fn ($clients) => collect($clients)->contains('id', $ctx['client']->id))
    );
});

test('the create page ignores a client query param that is not an active company client', function () {
    $ctx = makeSaleContext();

    $inactiveClient = \App\Modules\Client\Models\Client::factory()->create([
        'company_id' => $ctx['company']->id,
        'status' => 'inactive',
    ]);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->get(route('sales.create', [
            'company' => $ctx['company']->id,
            'client' => $inactiveClient->id,
        ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('sales/create')
        ->where('preselectedClientId', null)
    );
});

test('the show page returns the sale with its nested profiles and flags', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'active');

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->get(route('sales.show', ['company' => $ctx['company']->id, 'id' => $sale->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('sales/show')
        ->where('sale.id', $sale->id)
        ->where('sale.can_be_renewed', true)
        ->has('sale.sale_profiles', 1)
    );
});

test('the show page exposes the current plan price and duration, not the sale snapshot', function () {
    $ctx = makeSaleContext();
    $sale = persistSale($ctx, 'active');

    $ctx['plan']->update([
        'duration_days' => 60,
        'sale_price' => 75.00,
    ]);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->get(route('sales.show', ['company' => $ctx['company']->id, 'id' => $sale->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('sale.plan.duration_days', 60)
        ->where('sale.plan.sale_price', '75.00')
        ->where('sale.duration_days', 30)
        ->where('sale.price', '50.00')
    );
});

test('a user without list permission cannot view the index', function () {
    $ctx = makeSaleContext();
    assignRoleWithPermissions($ctx['user'], $ctx['company'], ['accounts.list']);

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->get(route('sales.index', ['company' => $ctx['company']->id]));

    $response->assertForbidden();
});
