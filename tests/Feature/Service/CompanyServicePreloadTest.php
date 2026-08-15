<?php

declare(strict_types=1);

use App\Modules\Service\Models\Service;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('creating a company preloads the streaming services catalog', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);

    $newCompanyId = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('companies.store', ['company' => $company->id]), [
            'id' => $newCompanyId,
            'name' => 'Streaming Co',
        ])
        ->assertSessionHasNoErrors();

    $services = Service::query()->where('company_id', $newCompanyId)->get();

    expect($services)->toHaveCount(count(config('streaming.default_services')));
    expect($services->pluck('code')->all())->toContain('SER000001');

    $netflix = $services->firstWhere('name', 'Netflix');
    expect($netflix)->not->toBeNull();
    expect($netflix->max_profiles)->toBe(5);
    expect($netflix->active)->toBeTrue();
    expect($netflix->logo_url)->toBe('/images/streaming/netflix.svg');
});

test('preloaded services are scoped to the new company only', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);

    $newCompanyId = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('companies.store', ['company' => $company->id]), [
            'id' => $newCompanyId,
            'name' => 'Another Co',
        ]);

    // The acting user's original company was created via the test helper
    // (not the service), so it has no preloaded services.
    expect(Service::query()->where('company_id', $company->id)->count())->toBe(0);
    expect(Service::query()->where('company_id', $newCompanyId)->count())
        ->toBe(count(config('streaming.default_services')));
});
