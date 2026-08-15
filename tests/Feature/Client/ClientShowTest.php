<?php

declare(strict_types=1);

use App\Modules\Client\Exceptions\ClientNotFoundException;
use App\Modules\Client\Models\Client;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('the client show page renders', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create(['company_id' => $company->id, 'name' => 'Visible Client']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('clients.show', ['company' => $company->id, 'id' => $client->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('clients/show')
        ->where('client.id', $client->id)
        ->where('client.name', 'Visible Client')
    );
});

test('the client edit page renders', function () {
    [$user, $company] = createUserWithCompany();

    $client = Client::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('clients.edit', ['company' => $company->id, 'id' => $client->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('clients/edit')
        ->where('client.id', $client->id)
    );
});

test('showing a missing client throws a not found exception', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('clients.show', ['company' => $company->id, 'id' => (string) Str::uuid7()]));
})->throws(ClientNotFoundException::class);
