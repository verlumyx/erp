<?php

declare(strict_types=1);

use App\Modules\Tax\Exceptions\TaxNotFoundException;
use App\Modules\Tax\Models\Tax;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('the tax show page renders', function () {
    [$user, $company] = createUserWithCompany();

    $tax = Tax::factory()->create(['company_id' => $company->id, 'name' => 'IVA 15%']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('taxes.show', ['company' => $company->id, 'id' => $tax->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('taxes/show')
        ->where('tax.id', $tax->id)
        ->where('tax.name', 'IVA 15%')
    );
});

test('the tax edit page renders', function () {
    [$user, $company] = createUserWithCompany();

    $tax = Tax::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('taxes.edit', ['company' => $company->id, 'id' => $tax->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('taxes/edit')
        ->where('tax.id', $tax->id)
    );
});

test('showing a missing tax throws a not found exception', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('taxes.show', ['company' => $company->id, 'id' => (string) Str::uuid7()]));
})->throws(TaxNotFoundException::class);

test('a tax from another company is not visible', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = Tax::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('taxes.show', ['company' => $company->id, 'id' => $foreign->id]));
})->throws(TaxNotFoundException::class);
