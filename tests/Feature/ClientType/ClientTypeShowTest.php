<?php

declare(strict_types=1);

use App\Modules\ClientType\Exceptions\ClientTypeNotFoundException;
use App\Modules\ClientType\Models\ClientType;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('the client type show page renders', function () {
    [$user, $company] = createUserWithCompany();

    $clientType = ClientType::factory()->create(['company_id' => $company->id, 'name' => 'Mayorista']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('client-types.show', ['company' => $company->id, 'id' => $clientType->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('client-types/show')
        ->where('clientType.id', $clientType->id)
        ->where('clientType.name', 'Mayorista')
    );
});

test('the client type edit page renders', function () {
    [$user, $company] = createUserWithCompany();

    $clientType = ClientType::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('client-types.edit', ['company' => $company->id, 'id' => $clientType->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('client-types/edit')
        ->where('clientType.id', $clientType->id)
    );
});

test('showing a missing client type throws a not found exception', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('client-types.show', ['company' => $company->id, 'id' => (string) Str::uuid7()]));
})->throws(ClientTypeNotFoundException::class);

test('a client type from another company is not visible', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = ClientType::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('client-types.show', ['company' => $company->id, 'id' => $foreign->id]));
})->throws(ClientTypeNotFoundException::class);
