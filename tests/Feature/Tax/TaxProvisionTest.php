<?php

declare(strict_types=1);

use App\Modules\Company\Commands\CreateCompanyCommand;
use App\Modules\Company\Services\CompanyCreateService;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Services\TaxProvisionService;
use Illuminate\Support\Str;

test('creating a company automatically provisions standard Venezuelan taxes', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);

    $newCompanyId = (string) Str::uuid7();

    $service = app(CompanyCreateService::class);
    $service->execute(new CreateCompanyCommand(
        id: $newCompanyId,
        name: 'Inversiones Venezuela C.A.',
        createdBy: $user->id,
        rif: 'J-12345678-9',
        fiscalAddress: 'Av. Francisco de Miranda, Caracas',
        taxpayerType: 'ordinary',
    ));

    $taxes = Tax::query()->where('company_id', $newCompanyId)->orderBy('code')->get();

    expect($taxes)->toHaveCount(4);

    $exempt = $taxes->firstWhere('tax_type', Tax::TYPE_EXEMPT);
    expect($exempt)->not->toBeNull()
        ->and((float) $exempt->percentage)->toBe(0.0)
        ->and($exempt->name)->toContain('Exento');

    $general = $taxes->firstWhere('tax_type', Tax::TYPE_GENERAL);
    expect($general)->not->toBeNull()
        ->and((float) $general->percentage)->toBe(16.0)
        ->and($general->name)->toContain('16%');

    $reduced = $taxes->firstWhere('tax_type', Tax::TYPE_REDUCED);
    expect($reduced)->not->toBeNull()
        ->and((float) $reduced->percentage)->toBe(8.0)
        ->and($reduced->name)->toContain('8%');

    $additional = $taxes->firstWhere('tax_type', Tax::TYPE_ADDITIONAL);
    expect($additional)->not->toBeNull()
        ->and((float) $additional->percentage)->toBe(31.0)
        ->and($additional->name)->toContain('31%');
});

test('tax provision service is idempotent', function () {
    [$user, $company] = createUserWithCompany();

    $provisionService = app(TaxProvisionService::class);

    // First call
    $provisionService->provisionDefaults($company->id, $user->id);
    $initialCount = Tax::query()->where('company_id', $company->id)->count();

    expect($initialCount)->toBe(4);

    // Second call should not duplicate
    $provisionService->provisionDefaults($company->id, $user->id);
    $afterCount = Tax::query()->where('company_id', $company->id)->count();

    expect($afterCount)->toBe(4);
});
