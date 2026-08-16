<?php

declare(strict_types=1);

use App\Modules\PriceList\Models\PriceList;

use function Pest\Laravel\actingAs;

test('a price list can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $priceList = PriceList::factory()->create([
        'company_id' => $company->id,
        'name' => 'Nombre viejo',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('price-lists.update', ['company' => $company->id, 'id' => $priceList->id]), [
            'name' => 'Nombre nuevo',
            'description' => 'Descripción actualizada',
        ]);

    $response->assertRedirect(route('price-lists.show', ['company' => $company->id, 'id' => $priceList->id]));
    $response->assertSessionHasNoErrors();

    $priceList->refresh();
    expect($priceList->name)->toBe('Nombre nuevo');
    expect($priceList->description)->toBe('Descripción actualizada');
});

test('a price list keeps its own name when updating', function () {
    [$user, $company] = createUserWithCompany();

    $priceList = PriceList::factory()->create(['company_id' => $company->id, 'name' => 'Mayorista']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('price-lists.update', ['company' => $company->id, 'id' => $priceList->id]), [
            'name' => 'Mayorista',
        ]);

    $response->assertSessionHasNoErrors();
});

test('a price list cannot take another price lists name', function () {
    [$user, $company] = createUserWithCompany();

    PriceList::factory()->create(['company_id' => $company->id, 'name' => 'Ocupada']);
    $priceList = PriceList::factory()->create(['company_id' => $company->id, 'name' => 'Propia']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('price-lists.update', ['company' => $company->id, 'id' => $priceList->id]), [
            'name' => 'Ocupada',
        ]);

    $response->assertSessionHasErrors('name');
});

test('a user without permission cannot update a price list', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['price-lists.list']);

    $priceList = PriceList::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('price-lists.update', ['company' => $company->id, 'id' => $priceList->id]), [
            'name' => 'Prohibida',
        ]);

    $response->assertForbidden();
});
