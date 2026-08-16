<?php

declare(strict_types=1);

use App\Modules\MeasurementUnit\Exceptions\MeasurementUnitNotFoundException;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('the measurement unit show page renders', function () {
    [$user, $company] = createUserWithCompany();

    $unit = MeasurementUnit::factory()->create(['company_id' => $company->id, 'name' => 'Kilogramo']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('measurement-units.show', ['company' => $company->id, 'id' => $unit->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('measurement-units/show')
        ->where('measurementUnit.id', $unit->id)
        ->where('measurementUnit.name', 'Kilogramo')
    );
});

test('the measurement unit edit page renders', function () {
    [$user, $company] = createUserWithCompany();

    $unit = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('measurement-units.edit', ['company' => $company->id, 'id' => $unit->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('measurement-units/edit')
        ->where('measurementUnit.id', $unit->id)
    );
});

test('showing a missing measurement unit throws a not found exception', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('measurement-units.show', ['company' => $company->id, 'id' => (string) Str::uuid7()]));
})->throws(MeasurementUnitNotFoundException::class);

test('a measurement unit from another company is not visible', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = MeasurementUnit::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('measurement-units.show', ['company' => $company->id, 'id' => $foreign->id]));
})->throws(MeasurementUnitNotFoundException::class);
