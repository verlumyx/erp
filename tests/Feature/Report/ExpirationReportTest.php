<?php

declare(strict_types=1);

use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleRenewal;

use function Pest\Laravel\actingAs;

/**
 * Crea una venta de la compañía con el estado y vencimiento indicados.
 */
function makeExpirationSale(string $companyId, string $status, string $endDate): Sale
{
    return Sale::factory()->create([
        'company_id' => $companyId,
        'status' => $status,
        'end_date' => $endDate,
        'price' => 100,
    ]);
}

test('a user without permission cannot view the expirations report', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.expirations.index', ['company' => $company->id]));

    $response->assertForbidden();
});

test('the expirations report renders empty until a search is performed', function () {
    [$user, $company] = createUserWithCompany();

    makeExpirationSale($company->id, Sale::STATUS_ACTIVE, now()->addDays(3)->toDateString());

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.expirations.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('reports/expirations/index')
        ->where('searched', false)
        ->has('sales', 0)
        ->where('meta.total', 0)
        ->where('summary.expiring_count', 0)
        ->where('summary.expired_count', 0)
        ->where('summary.renewal_rate', 0)
    );
});

test('the expirations report filters active sales by the upcoming-days range', function () {
    [$user, $company] = createUserWithCompany();

    $soon = makeExpirationSale($company->id, Sale::STATUS_ACTIVE, now()->addDays(3)->toDateString());
    makeExpirationSale($company->id, Sale::STATUS_ACTIVE, now()->addDays(20)->toDateString());
    makeExpirationSale($company->id, Sale::STATUS_EXPIRED, now()->subDays(5)->toDateString());

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.expirations.index', [
            'company' => $company->id,
            'days' => 7,
            'status' => 'expiring',
            'searched' => '1',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->where('searched', true)
        ->has('sales', 1)
        ->where('sales.0.code', $soon->code)
        ->where('summary.expiring_count', 1)
    );
});

test('the expirations report lists expired sales without renewal when status is expired', function () {
    [$user, $company] = createUserWithCompany();

    makeExpirationSale($company->id, Sale::STATUS_EXPIRED, now()->subDays(2)->toDateString());
    makeExpirationSale($company->id, Sale::STATUS_EXPIRED, now()->subDays(10)->toDateString());
    makeExpirationSale($company->id, Sale::STATUS_ACTIVE, now()->addDays(4)->toDateString());

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.expirations.index', [
            'company' => $company->id,
            'status' => 'expired',
            'searched' => '1',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->has('sales', 2)
        ->where('sales.0.status', Sale::STATUS_EXPIRED)
        ->where('sales.1.status', Sale::STATUS_EXPIRED)
        ->where('meta.total', 2)
    );
});

test('the expirations renewal rate crosses sales with their renewals', function () {
    [$user, $company] = createUserWithCompany();

    // Dos ventas vencidas sin renovar dentro de la ventana.
    makeExpirationSale($company->id, Sale::STATUS_EXPIRED, '2026-03-15');
    makeExpirationSale($company->id, Sale::STATUS_EXPIRED, '2026-03-22');

    // Una venta renovada: su vencimiento original (previous_end_date) cae en la ventana.
    $renewed = makeExpirationSale($company->id, Sale::STATUS_ACTIVE, '2026-04-20');
    SaleRenewal::factory()->forSale($renewed)->create([
        'previous_end_date' => '2026-03-10',
        'new_end_date' => '2026-04-20',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.expirations.index', [
            'company' => $company->id,
            'status' => 'expired',
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
            'searched' => '1',
        ]));

    // 1 renovada de 3 vencimientos en la ventana → 33.33%.
    $response->assertInertia(fn ($page) => $page
        ->where('summary.expired_count', 2)
        ->where('summary.renewal_rate', 33.33)
    );
});

test('the expirations report only shows sales from the active company', function () {
    [$user, $company] = createUserWithCompany();

    makeExpirationSale($company->id, Sale::STATUS_ACTIVE, now()->addDays(3)->toDateString());

    $other = \App\Modules\Company\Models\Company::create([
        'name' => 'Other '.uniqid(),
        'status' => 'active',
        'created_by' => $user->id,
    ]);
    makeExpirationSale($other->id, Sale::STATUS_ACTIVE, now()->addDays(3)->toDateString());

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('reports.expirations.index', [
            'company' => $company->id,
            'days' => 7,
            'status' => 'expiring',
            'searched' => '1',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->has('sales', 1)
        ->where('meta.total', 1)
    );
});
