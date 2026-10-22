<?php

declare(strict_types=1);

use App\Modules\Company\Commands\CreateCompanyCommand;
use App\Modules\Company\Services\CompanyCreateService;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\MeasurementUnit\Services\MeasurementUnitProvisionService;
use Illuminate\Support\Str;

test('creating a company automatically provisions standard basic measurement units', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);

    $newCompanyId = (string) Str::uuid7();

    $service = app(CompanyCreateService::class);
    $service->execute(new CreateCompanyCommand(
        id: $newCompanyId,
        name: 'Distribuidora Los Andes C.A.',
        createdBy: $user->id,
        rif: 'J-99887766-5',
        fiscalAddress: 'Av. Las Américas, Mérida',
        taxpayerType: 'ordinary',
    ));

    $units = MeasurementUnit::query()
        ->where('company_id', $newCompanyId)
        ->orderBy('code')
        ->get();

    expect($units)->toHaveCount(5);

    $unit = $units->firstWhere('abbreviation', 'UND');
    expect($unit)->not->toBeNull()
        ->and($unit->name)->toBe('Unidad')
        ->and($unit->code)->toBe('UOM000001');

    $serviceUnit = $units->firstWhere('abbreviation', 'SERV');
    expect($serviceUnit)->not->toBeNull()
        ->and($serviceUnit->name)->toBe('Servicio')
        ->and($serviceUnit->code)->toBe('UOM000002');

    $kg = $units->firstWhere('abbreviation', 'KG');
    expect($kg)->not->toBeNull()
        ->and($kg->name)->toBe('Kilogramo')
        ->and($kg->code)->toBe('UOM000003');

    $lt = $units->firstWhere('abbreviation', 'LT');
    expect($lt)->not->toBeNull()
        ->and($lt->name)->toBe('Litro')
        ->and($lt->code)->toBe('UOM000004');

    $box = $units->firstWhere('abbreviation', 'CJ');
    expect($box)->not->toBeNull()
        ->and($box->name)->toBe('Caja')
        ->and($box->code)->toBe('UOM000005');
});

test('measurement unit provision service is idempotent', function () {
    [$user, $company] = createUserWithCompany();

    $provisionService = app(MeasurementUnitProvisionService::class);

    // First call
    $provisionService->provisionDefaults($company->id, $user->id);
    $initialCount = MeasurementUnit::query()->where('company_id', $company->id)->count();

    expect($initialCount)->toBe(5);

    // Second call should not duplicate
    $provisionService->provisionDefaults($company->id, $user->id);
    $afterCount = MeasurementUnit::query()->where('company_id', $company->id)->count();

    expect($afterCount)->toBe(5);
});
