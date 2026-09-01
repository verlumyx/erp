<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\SalesReturn\Models\SalesReturn;

use function Pest\Laravel\actingAs;

/** Una devolución de la empresa activa lista para acreditarse. */
function creditableSalesReturn(
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    array $overrides = [],
): SalesReturn {
    return SalesReturn::factory()->confirmed()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        ...$overrides,
    ]);
}

test('the lookup only offers returns that can still be credited', function () {
    [$user, $company, $client, $warehouse] = salesReturnScenario();

    $creditable = creditableSalesReturn($company, $client, $warehouse);

    /** En borrador la mercancía no ha salido. */
    SalesReturn::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    /** Anulada tampoco hay qué acreditar. */
    SalesReturn::factory()->cancelled()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales-returns.lookup', ['company' => $company->id]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($creditable->id);
    expect($response->json('data.0.meta.client_id'))->toBe($client->id);
    expect($response->json('data.0.meta.client_name'))->toBe($client->name);
});

test('the lookup can be narrowed to one client', function () {
    [$user, $company, $client, $warehouse] = salesReturnScenario();

    $other = Client::factory()->create(['company_id' => $company->id]);

    creditableSalesReturn($company, $client, $warehouse);
    $mine = creditableSalesReturn($company, $other, $warehouse);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales-returns.lookup', [
            'company' => $company->id,
            'client_id' => $other->id,
        ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($mine->id);
});

test('the lookup searches by code', function () {
    [$user, $company, $client, $warehouse] = salesReturnScenario();

    $wanted = creditableSalesReturn($company, $client, $warehouse, [
        'code' => 'DVV777777',
    ]);

    creditableSalesReturn($company, $client, $warehouse, ['code' => 'DVV000123']);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales-returns.lookup', [
            'company' => $company->id,
            'q' => '777777',
        ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($wanted->id);

    $byCode = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales-returns.lookup', [
            'company' => $company->id,
            'q' => $wanted->code,
        ]));

    expect($byCode->json('data.0.value'))->toBe($wanted->id);
});

/**
 * Hidratar lo ya elegido no filtra por estado: la devolución que una nota
 * acredita ya no es «acreditable», pero sigue siendo la suya.
 */
test('hydrating by ids returns an already credited return', function () {
    [$user, $company, $client, $warehouse] = salesReturnScenario();

    $credited = SalesReturn::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'completed',
        'credit_note_id' => \App\Modules\SalesCreditNote\Models\SalesCreditNote::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ])->id,
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales-returns.lookup', [
            'company' => $company->id,
            'ids' => $credited->id,
        ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($credited->id);
});

test('the lookup never crosses companies', function () {
    [$user, $company] = salesReturnScenario();

    SalesReturn::factory()->confirmed()->create();

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales-returns.lookup', ['company' => $company->id]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(0);
});

test('a user without permission cannot use the lookup', function () {
    [$user, $company] = salesReturnScenario();
    assignRoleWithPermissions($user, $company, ['clients.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales-returns.lookup', ['company' => $company->id]))
        ->assertForbidden();
});
