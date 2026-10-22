<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Client\Services\ClientProvisionService;
use App\Modules\Company\Commands\CreateCompanyCommand;
use App\Modules\Company\Services\CompanyCreateService;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\PriceList\Services\PriceListProvisionService;
use App\Modules\Tax\Models\Tax;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Services\WarehouseProvisionService;
use Illuminate\Support\Str;

test('creating a company automatically provisions standard defaults including taxes, units, price list, client and warehouse', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);

    $newCompanyId = (string) Str::uuid7();

    $service = app(CompanyCreateService::class);
    $service->execute(new CreateCompanyCommand(
        id: $newCompanyId,
        name: 'Inversiones Globales 2026 C.A.',
        createdBy: $user->id,
        rif: 'J-55443322-1',
        fiscalAddress: 'Av. Bolívar, Valencia',
        taxpayerType: 'ordinary',
    ));

    // Taxes
    expect(Tax::query()->where('company_id', $newCompanyId)->count())->toBe(4);

    // Measurement Units
    expect(MeasurementUnit::query()->where('company_id', $newCompanyId)->count())->toBe(5);

    // Price List
    $priceList = PriceList::query()->where('company_id', $newCompanyId)->first();
    expect($priceList)->not->toBeNull()
        ->and($priceList->name)->toBe('Precio regular');

    // Default Client
    $client = Client::query()->where('company_id', $newCompanyId)->first();
    expect($client)->not->toBeNull()
        ->and($client->name)->toBe('Consumidor Final')
        ->and($client->document_type)->toBe('V')
        ->and($client->document_number)->toBe('0000000')
        ->and($client->price_list_id)->toBe($priceList->id);

    // Default Warehouse
    $warehouse = Warehouse::query()->where('company_id', $newCompanyId)->first();
    expect($warehouse)->not->toBeNull()
        ->and($warehouse->name)->toBe('Bodega general')
        ->and($warehouse->is_default)->toBe('yes')
        ->and($warehouse->uses_locations)->toBe('yes')
        ->and($warehouse->locations)->toHaveCount(1)
        ->and($warehouse->locations->first()->location_code)->toBe('PRINCIPAL');
});

test('price list, client and warehouse provision services are idempotent', function () {
    [$user, $company] = createUserWithCompany();

    $priceListService = app(PriceListProvisionService::class);
    $clientService = app(ClientProvisionService::class);
    $warehouseService = app(WarehouseProvisionService::class);

    // Call first time
    $pl = $priceListService->provisionDefaults($company->id, $user->id);
    $clientService->provisionDefaults($company->id, $user->id, $pl->id);
    $warehouseService->provisionDefaults($company->id, $user->id);

    $plCount = PriceList::query()->where('company_id', $company->id)->where('name', 'Precio regular')->count();
    $clientCount = Client::query()->where('company_id', $company->id)->where('name', 'Consumidor Final')->count();
    $warehouseCount = Warehouse::query()->where('company_id', $company->id)->where('name', 'Bodega general')->count();

    expect($plCount)->toBe(1)
        ->and($clientCount)->toBe(1)
        ->and($warehouseCount)->toBe(1);

    // Call second time
    $pl2 = $priceListService->provisionDefaults($company->id, $user->id);
    $clientService->provisionDefaults($company->id, $user->id, $pl2->id);
    $warehouseService->provisionDefaults($company->id, $user->id);

    $plCountAfter = PriceList::query()->where('company_id', $company->id)->where('name', 'Precio regular')->count();
    $clientCountAfter = Client::query()->where('company_id', $company->id)->where('name', 'Consumidor Final')->count();
    $warehouseCountAfter = Warehouse::query()->where('company_id', $company->id)->where('name', 'Bodega general')->count();

    expect($plCountAfter)->toBe(1)
        ->and($clientCountAfter)->toBe(1)
        ->and($warehouseCountAfter)->toBe(1);
});
