<?php

declare(strict_types=1);

use App\Modules\Tax\Models\Tax;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a tax can be created', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('taxes.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'IVA 15%',
            'description' => 'Impuesto al valor agregado',
            'percentage' => 15,
            'has_withholding' => 'no',
        ]);

    $response->assertRedirect(route('taxes.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $tax = Tax::find($id);
    expect($tax)->not->toBeNull();
    expect($tax->name)->toBe('IVA 15%');
    expect((float) $tax->percentage)->toBe(15.0);
    expect($tax->has_withholding)->toBe('no');
    expect((float) $tax->withholding_percentage)->toBe(0.0);
    expect($tax->status)->toBe('active');
    expect($tax->created_by)->toBe($user->id);
    expect($tax->company_id)->toBe($company->id);
    expect($tax->code)->toBe('IMP000001');
});

test('a tax can be created with a withholding', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('taxes.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'IVA con retención',
            'percentage' => 16,
            'has_withholding' => 'yes',
            'withholding_percentage' => 75,
        ]);

    $response->assertSessionHasNoErrors();

    $tax = Tax::find($id);
    expect($tax->has_withholding)->toBe('yes');
    expect((float) $tax->withholding_percentage)->toBe(75.0);
});

test('the withholding percentage is forced to zero without withholding', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('taxes.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'IVA sin retención',
            'percentage' => 16,
            'has_withholding' => 'no',
            'withholding_percentage' => 75,
        ])
        ->assertSessionHasNoErrors();

    expect((float) Tax::find($id)->withholding_percentage)->toBe(0.0);
});

test('an exempt tax with zero percentage can be created', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('taxes.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Exento',
            'percentage' => 0,
            'has_withholding' => 'no',
        ])
        ->assertSessionHasNoErrors();

    expect((float) Tax::find($id)->percentage)->toBe(0.0);
});

test('the code auto-increments per company', function () {
    [$user, $company] = createUserWithCompany();

    $first = (string) Str::uuid7();
    $second = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('taxes.store', ['company' => $company->id]), [
            'id' => $first,
            'name' => 'IVA',
            'percentage' => 16,
            'has_withholding' => 'no',
        ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('taxes.store', ['company' => $company->id]), [
            'id' => $second,
            'name' => 'IGTF',
            'percentage' => 3,
            'has_withholding' => 'no',
        ]);

    expect(Tax::find($first)->code)->toBe('IMP000001');
    expect(Tax::find($second)->code)->toBe('IMP000002');
});

test('each company has its own tax code sequence', function () {
    [$userA, $companyA] = createUserWithCompany();
    [$userB, $companyB] = createUserWithCompany();

    $idA = (string) Str::uuid7();
    $idB = (string) Str::uuid7();

    actingAs($userA)
        ->withSession(['current_company_id' => $companyA->id])
        ->post(route('taxes.store', ['company' => $companyA->id]), [
            'id' => $idA,
            'name' => 'IVA',
            'percentage' => 16,
            'has_withholding' => 'no',
        ]);

    actingAs($userB)
        ->withSession(['current_company_id' => $companyB->id])
        ->post(route('taxes.store', ['company' => $companyB->id]), [
            'id' => $idB,
            'name' => 'IVA',
            'percentage' => 16,
            'has_withholding' => 'no',
        ]);

    expect(Tax::find($idA)->code)->toBe('IMP000001');
    expect(Tax::find($idB)->code)->toBe('IMP000001');
});

test('the name is required', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('taxes.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => '',
            'percentage' => 16,
            'has_withholding' => 'no',
        ]);

    $response->assertSessionHasErrors('name');
});

test('the percentage is required', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('taxes.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'IVA',
            'has_withholding' => 'no',
        ]);

    $response->assertSessionHasErrors('percentage');
});

test('the percentage cannot be negative', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('taxes.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'IVA',
            'percentage' => -1,
            'has_withholding' => 'no',
        ]);

    $response->assertSessionHasErrors('percentage');
});

test('the withholding percentage is required when there is withholding', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('taxes.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'IVA',
            'percentage' => 16,
            'has_withholding' => 'yes',
        ]);

    $response->assertSessionHasErrors('withholding_percentage');
});

test('the name must be unique per company', function () {
    [$user, $company] = createUserWithCompany();

    Tax::factory()->create(['company_id' => $company->id, 'name' => 'IVA 15%']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('taxes.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'IVA 15%',
            'percentage' => 15,
            'has_withholding' => 'no',
        ]);

    $response->assertSessionHasErrors('name');
});

test('another company can reuse the same tax name', function () {
    [$user, $company] = createUserWithCompany();

    Tax::factory()->create(['name' => 'IVA 15%']);

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('taxes.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'IVA 15%',
            'percentage' => 15,
            'has_withholding' => 'no',
        ]);

    $response->assertSessionHasNoErrors();
    expect(Tax::find($id))->not->toBeNull();
});

test('a user without permission cannot create a tax', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['taxes.list']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('taxes.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Forbidden',
            'percentage' => 16,
            'has_withholding' => 'no',
        ]);

    $response->assertForbidden();
});
