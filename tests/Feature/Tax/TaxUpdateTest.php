<?php

declare(strict_types=1);

use App\Modules\Tax\Models\Tax;

use function Pest\Laravel\actingAs;

test('a tax can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $tax = Tax::factory()->create([
        'company_id' => $company->id,
        'name' => 'IVA',
        'percentage' => 12,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('taxes.update', ['company' => $company->id, 'id' => $tax->id]), [
            'name' => 'IVA 16%',
            'description' => 'Alícuota general',
            'percentage' => 16,
            'has_withholding' => 'no',
        ]);

    $response->assertRedirect(route('taxes.show', ['company' => $company->id, 'id' => $tax->id]));
    $response->assertSessionHasNoErrors();

    $tax->refresh();
    expect($tax->name)->toBe('IVA 16%');
    expect((float) $tax->percentage)->toBe(16.0);
    expect($tax->description)->toBe('Alícuota general');
});

test('turning the withholding on stores its percentage', function () {
    [$user, $company] = createUserWithCompany();

    $tax = Tax::factory()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('taxes.update', ['company' => $company->id, 'id' => $tax->id]), [
            'name' => $tax->name,
            'percentage' => 16,
            'has_withholding' => 'yes',
            'withholding_percentage' => 100,
        ])
        ->assertSessionHasNoErrors();

    $tax->refresh();
    expect($tax->has_withholding)->toBe('yes');
    expect((float) $tax->withholding_percentage)->toBe(100.0);
});

test('turning the withholding off resets its percentage to zero', function () {
    [$user, $company] = createUserWithCompany();

    $tax = Tax::factory()->withWithholding()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('taxes.update', ['company' => $company->id, 'id' => $tax->id]), [
            'name' => $tax->name,
            'percentage' => 16,
            'has_withholding' => 'no',
            'withholding_percentage' => 75,
        ])
        ->assertSessionHasNoErrors();

    $tax->refresh();
    expect($tax->has_withholding)->toBe('no');
    expect((float) $tax->withholding_percentage)->toBe(0.0);
});

test('updating keeps its own name valid', function () {
    [$user, $company] = createUserWithCompany();

    $tax = Tax::factory()->create(['company_id' => $company->id, 'name' => 'IVA']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('taxes.update', ['company' => $company->id, 'id' => $tax->id]), [
            'name' => 'IVA',
            'description' => 'Actualizado',
            'percentage' => 16,
            'has_withholding' => 'no',
        ]);

    $response->assertSessionHasNoErrors();
    expect($tax->fresh()->description)->toBe('Actualizado');
});

test('the updated name must not collide with another tax', function () {
    [$user, $company] = createUserWithCompany();

    Tax::factory()->create(['company_id' => $company->id, 'name' => 'IVA']);
    $tax = Tax::factory()->create(['company_id' => $company->id, 'name' => 'IGTF']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('taxes.update', ['company' => $company->id, 'id' => $tax->id]), [
            'name' => 'IVA',
            'percentage' => 16,
            'has_withholding' => 'no',
        ]);

    $response->assertSessionHasErrors('name');
    expect($tax->fresh()->name)->toBe('IGTF');
});

test('a user without permission cannot update a tax', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['taxes.list']);

    $tax = Tax::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('taxes.update', ['company' => $company->id, 'id' => $tax->id]), [
            'name' => 'Forbidden',
            'percentage' => 16,
            'has_withholding' => 'no',
        ]);

    $response->assertForbidden();
});
