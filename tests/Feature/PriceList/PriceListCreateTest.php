<?php

declare(strict_types=1);

use App\Modules\PriceList\Models\PriceList;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a price list can be created', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('price-lists.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Mayorista',
            'description' => 'Precios para revendedores',
        ]);

    $response->assertRedirect(route('price-lists.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $priceList = PriceList::find($id);
    expect($priceList)->not->toBeNull();
    expect($priceList->name)->toBe('Mayorista');
    expect($priceList->description)->toBe('Precios para revendedores');
    expect($priceList->status)->toBe('active');
    expect($priceList->created_by)->toBe($user->id);
    expect($priceList->company_id)->toBe($company->id);
    expect($priceList->code)->toBe('PRL000001');
});

test('the code auto-increments per company', function () {
    [$user, $company] = createUserWithCompany();

    $first = (string) Str::uuid7();
    $second = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('price-lists.store', ['company' => $company->id]), [
            'id' => $first,
            'name' => 'Primera',
        ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('price-lists.store', ['company' => $company->id]), [
            'id' => $second,
            'name' => 'Segunda',
        ]);

    expect(PriceList::find($first)->code)->toBe('PRL000001');
    expect(PriceList::find($second)->code)->toBe('PRL000002');
});

test('each company has its own code sequence', function () {
    [$userA, $companyA] = createUserWithCompany();
    [$userB, $companyB] = createUserWithCompany();

    $idA = (string) Str::uuid7();
    $idB = (string) Str::uuid7();

    actingAs($userA)
        ->withSession(['current_company_id' => $companyA->id])
        ->post(route('price-lists.store', ['company' => $companyA->id]), [
            'id' => $idA,
            'name' => 'Lista A',
        ]);

    actingAs($userB)
        ->withSession(['current_company_id' => $companyB->id])
        ->post(route('price-lists.store', ['company' => $companyB->id]), [
            'id' => $idB,
            'name' => 'Lista B',
        ]);

    expect(PriceList::find($idA)->code)->toBe('PRL000001');
    expect(PriceList::find($idB)->code)->toBe('PRL000001');
});

test('a price list can be created without a description', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('price-lists.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Mínima',
        ]);

    $response->assertSessionHasNoErrors();

    $priceList = PriceList::find($id);
    expect($priceList?->name)->toBe('Mínima');
    expect($priceList?->description)->toBeNull();
});

test('the name is required', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('price-lists.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => '',
        ]);

    $response->assertSessionHasErrors('name');
});

test('the name must be unique within the company', function () {
    [$user, $company] = createUserWithCompany();

    PriceList::factory()->create(['company_id' => $company->id, 'name' => 'Mayorista']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('price-lists.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Mayorista',
        ]);

    $response->assertSessionHasErrors('name');
});

test('another company can reuse the same price list name', function () {
    [$user, $company] = createUserWithCompany();

    PriceList::factory()->create(['name' => 'Mayorista']);

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('price-lists.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Mayorista',
        ]);

    $response->assertSessionHasNoErrors();
    expect(PriceList::find($id)?->name)->toBe('Mayorista');
});

test('a user without permission cannot create a price list', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['price-lists.list']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('price-lists.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Prohibida',
        ]);

    $response->assertForbidden();
});
