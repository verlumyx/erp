<?php

declare(strict_types=1);

use App\Modules\Account\Models\Account;
use App\Modules\Account\Models\Profile;
use App\Modules\Client\Models\Client;
use App\Modules\Dashboard\Services\DashboardExpirationsService;
use App\Modules\Dashboard\Services\DashboardMetricsService;
use App\Modules\Dashboard\Services\DashboardOccupancyService;
use App\Modules\Dashboard\Services\DashboardPlatformsService;
use App\Modules\Dashboard\Services\DashboardRevenueService;
use App\Modules\Sale\Models\Sale;
use App\Modules\Service\Models\Service;
use App\Modules\Transaction\Models\Transaction;

/**
 * Crea una cuenta con perfiles en los estados indicados para un servicio dado.
 *
 * @param  array{occupied?: int, available?: int, maintenance?: int}  $counts
 */
function seedProfiles(Service $service, array $counts): Account
{
    $account = Account::factory()->forService($service)->create();
    $number = 0;

    foreach (['occupied', 'available', 'maintenance'] as $status) {
        for ($i = 0; $i < ($counts[$status] ?? 0); $i++) {
            Profile::factory()->create([
                'account_id' => $account->id,
                'number' => ++$number,
                'status' => $status,
            ]);
        }
    }

    return $account;
}

test('occupancy service counts profiles by status scoped to the company', function () {
    [, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);
    seedProfiles($service, ['occupied' => 3, 'available' => 2, 'maintenance' => 1]);

    // Perfiles de otra compañía no deben contarse.
    seedProfiles(Service::factory()->create(), ['occupied' => 5]);

    $result = app(DashboardOccupancyService::class)->forCompany($company->id);

    expect($result)->toBe([
        'occupied' => 3,
        'available' => 2,
        'maintenance' => 1,
        'total' => 6,
    ]);
});

test('metrics service aggregates income, expense, profiles and receivables', function () {
    [, $company] = createUserWithCompany();

    Transaction::factory()->income()->create(['company_id' => $company->id, 'amount' => 100, 'date' => now()->toDateString()]);
    Transaction::factory()->income()->create(['company_id' => $company->id, 'amount' => 50, 'date' => now()->toDateString()]);
    Transaction::factory()->expense()->create(['company_id' => $company->id, 'amount' => 60, 'date' => now()->toDateString()]);

    $service = Service::factory()->create(['company_id' => $company->id]);
    seedProfiles($service, ['occupied' => 2, 'available' => 3]);

    $client = Client::factory()->create(['company_id' => $company->id]);
    Sale::factory()->expiredInGrace()->create(['company_id' => $company->id, 'client_id' => $client->id, 'price' => 30]);
    Sale::factory()->expiredOutOfGrace()->create(['company_id' => $company->id, 'client_id' => $client->id, 'price' => 20]);
    Sale::factory()->active()->create(['company_id' => $company->id, 'price' => 999]); // no cuenta como por cobrar

    $result = app(DashboardMetricsService::class)->forCompany($company->id);

    expect($result['income_month'])->toBe(150.0)
        ->and($result['expense_month'])->toBe(60.0)
        ->and($result['net_profit'])->toBe(90.0)
        ->and($result['profit_margin_pct'])->toBe(60)
        ->and($result['active_profiles'])->toBe(2)
        ->and($result['free_profiles'])->toBe(3)
        ->and($result['total_profiles'])->toBe(5)
        ->and($result['receivable_amount'])->toBe(50.0)
        ->and($result['receivable_clients'])->toBe(1);
});

test('metrics trend is null when there is no comparable previous month', function () {
    [, $company] = createUserWithCompany();
    Transaction::factory()->income()->create(['company_id' => $company->id, 'amount' => 100, 'date' => now()->toDateString()]);

    $result = app(DashboardMetricsService::class)->forCompany($company->id);

    expect($result['income_trend_pct'])->toBeNull();
});

test('revenue service returns six month buckets ending in the current month', function () {
    [, $company] = createUserWithCompany();

    Transaction::factory()->income()->create(['company_id' => $company->id, 'amount' => 200, 'date' => now()->toDateString()]);
    Transaction::factory()->expense()->create(['company_id' => $company->id, 'amount' => 80, 'date' => now()->toDateString()]);
    Transaction::factory()->income()->create(['company_id' => $company->id, 'amount' => 70, 'date' => now()->subMonthNoOverflow()->toDateString()]);
    // Fuera de la ventana de 6 meses: no debe aparecer.
    Transaction::factory()->income()->create(['company_id' => $company->id, 'amount' => 5000, 'date' => now()->subMonthsNoOverflow(8)->toDateString()]);

    $result = app(DashboardRevenueService::class)->lastSixMonths($company->id);

    expect($result)->toHaveCount(6);

    $current = $result[5];
    expect($current['income'])->toBe(200.0)
        ->and($current['expense'])->toBe(80.0)
        ->and($current['profit'])->toBe(120.0);

    expect($result[4]['income'])->toBe(70.0);

    $total = array_sum(array_column($result, 'income'));
    expect($total)->toBe(270.0); // los 5000 fuera de ventana quedan excluidos
});

test('platforms service ranks occupied profiles per service descending', function () {
    [, $company] = createUserWithCompany();

    $netflix = Service::factory()->create(['company_id' => $company->id, 'name' => 'Netflix']);
    $spotify = Service::factory()->create(['company_id' => $company->id, 'name' => 'Spotify']);

    seedProfiles($netflix, ['occupied' => 2, 'available' => 4]);
    seedProfiles($spotify, ['occupied' => 5]);

    $result = app(DashboardPlatformsService::class)->forCompany($company->id);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toMatchArray(['name' => 'Spotify', 'occupied' => 5])
        ->and($result[1])->toMatchArray(['name' => 'Netflix', 'occupied' => 2]);
});

test('expirations service lists soon-to-expire and overdue sales only', function () {
    $ctx = makeSaleContext();
    $company = $ctx['company'];

    Sale::factory()->active()->create([
        'company_id' => $company->id,
        'client_id' => $ctx['client']->id,
        'service_id' => $ctx['service']->id,
        'end_date' => now()->addDays(3)->toDateString(),
    ]);
    Sale::factory()->expiredInGrace()->create([
        'company_id' => $company->id,
        'client_id' => $ctx['client']->id,
        'service_id' => $ctx['service']->id,
        'end_date' => now()->subDays(2)->toDateString(),
    ]);
    // Vence en 30 días: fuera de la ventana de 9 días.
    Sale::factory()->active()->create([
        'company_id' => $company->id,
        'client_id' => $ctx['client']->id,
        'service_id' => $ctx['service']->id,
        'end_date' => now()->addDays(30)->toDateString(),
    ]);

    $result = app(DashboardExpirationsService::class)->upcoming($company->id);

    expect($result)->toHaveCount(2)
        ->and($result[0]['status_key'])->toBe('vencido')
        ->and($result[0]['days'])->toBe(-2)
        ->and($result[1]['status_key'])->toBe('porvencer')
        ->and($result[1]['days'])->toBe(3);
});
