<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\Company\Models\Company;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the detail carries the advance with its client and its mirror collection', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = clientAdvanceScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $advance = createClientAdvance($user, $company, $client, [
        'sales_order_id' => $order->id,
        'amount' => 400,
    ]);

    moveClientAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-advances.show', ['company' => $company->id, 'id' => $advance->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client-advances/show')
            ->where('clientAdvance.code', 'ANC000001')
            ->where('clientAdvance.status', 'pending_confirmation')
            ->where('clientAdvance.client_name', $client->name)
            ->where('clientAdvance.sales_order_code', $order->code)
            ->where('clientAdvance.collection_code', 'COB000001')
            ->where('clientAdvance.collection_status', 'draft'));
});

test('an advance of another company is not reachable', function () {
    [$user, $company] = clientAdvanceScenario();

    $otherCompany = Company::create([
        'name' => 'Otra empresa',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $advance = ClientAdvance::factory()->create([
        'company_id' => $otherCompany->id,
        'client_id' => Client::factory()->create(['company_id' => $otherCompany->id])->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-advances.show', ['company' => $company->id, 'id' => $advance->id]))
        ->assertNotFound();
});

test('a user without permission cannot see the detail', function () {
    [$user, $company, $client] = clientAdvanceScenario();

    $advance = createClientAdvance($user, $company, $client);

    assignRoleWithPermissions($user, $company, ['client-advances.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('client-advances.show', ['company' => $company->id, 'id' => $advance->id]))
        ->assertForbidden();
});
