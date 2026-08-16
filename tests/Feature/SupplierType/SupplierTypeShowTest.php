<?php

declare(strict_types=1);

use App\Modules\SupplierType\Exceptions\SupplierTypeNotFoundException;
use App\Modules\SupplierType\Models\SupplierType;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('the supplier type show page renders', function () {
    [$user, $company] = createUserWithCompany();

    $supplierType = SupplierType::factory()->create(['company_id' => $company->id, 'name' => 'Nacional']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-types.show', ['company' => $company->id, 'id' => $supplierType->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('supplier-types/show')
        ->where('supplierType.id', $supplierType->id)
        ->where('supplierType.name', 'Nacional')
    );
});

test('the supplier type edit page renders', function () {
    [$user, $company] = createUserWithCompany();

    $supplierType = SupplierType::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-types.edit', ['company' => $company->id, 'id' => $supplierType->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('supplier-types/edit')
        ->where('supplierType.id', $supplierType->id)
    );
});

test('showing a missing supplier type throws a not found exception', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('supplier-types.show', ['company' => $company->id, 'id' => (string) Str::uuid7()]));
})->throws(SupplierTypeNotFoundException::class);

test('a supplier type from another company is not visible', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = SupplierType::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('supplier-types.show', ['company' => $company->id, 'id' => $foreign->id]));
})->throws(SupplierTypeNotFoundException::class);
