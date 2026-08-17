<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\Configuration\Models\Configuration;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/**
 * La configuración no se crea desde ninguna pantalla: nace con la empresa, así
 * que ninguna empresa puede existir sin ella.
 */
test('creating a company creates its configuration', function () {
    [$owner, $company] = createUserWithCompany();
    $owner->update(['is_system_owner' => true]);

    $id = (string) Str::uuid7();

    actingAs($owner)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('companies.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Distribuidora Andina C.A.',
        ])
        ->assertSessionHasNoErrors();

    $configuration = Configuration::query()->where('company_id', $id)->first();

    expect($configuration)->not->toBeNull()
        ->and($configuration->base_currency)->toBe('USD')
        ->and($configuration->secondary_currency)->toBe('VES')
        ->and($configuration->rate_type)->toBe('legal')
        ->and($configuration->allows_rate_override)->toBe('yes')
        ->and($configuration->created_by)->toBe($owner->id);
});

test('deleting a company takes its configuration with it', function () {
    [, $company] = createUserWithCompany();

    expect(Configuration::query()->where('company_id', $company->id)->count())->toBe(1);

    Company::query()->where('id', $company->id)->delete();

    expect(Configuration::query()->where('company_id', $company->id)->count())->toBe(0);
});
