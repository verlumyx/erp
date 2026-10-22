<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('system owner can create a company with fiscal fields', function () {
    [$user, $existingCompany] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);

    $id = (string) Str::uuid();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $existingCompany->id])
        ->post(route('companies.store', ['company' => $existingCompany->id]), [
            'id' => $id,
            'name' => 'Inversiones Venezuela C.A.',
            'rif' => 'J-12345678-9',
            'fiscal_address' => 'Av. Francisco de Miranda, Torre Delta, Piso 5, Caracas',
            'address' => 'Sucursal Chacao',
            'phone' => '0212-5559988',
            'taxpayer_type' => 'special',
            'description' => 'Empresa de prueba fiscal',
        ]);

    $response->assertRedirect(route('companies.index', ['company' => $existingCompany->id]));

    $created = Company::find($id);
    expect($created)->not->toBeNull()
        ->and($created->name)->toBe('Inversiones Venezuela C.A.')
        ->and($created->rif)->toBe('J-12345678-9')
        ->and($created->fiscal_address)->toBe('Av. Francisco de Miranda, Torre Delta, Piso 5, Caracas')
        ->and($created->address)->toBe('Sucursal Chacao')
        ->and($created->phone)->toBe('0212-5559988')
        ->and($created->taxpayer_type)->toBe('special');
});

test('system owner can update fiscal fields of an existing company', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);

    $company->update([
        'name' => 'Empresa Antigua',
        'rif' => 'J-00000000-0',
        'taxpayer_type' => 'ordinary',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('companies.update', ['company' => $company->id, 'id' => $company->id]), [
            'name' => 'Empresa Actualizada',
            'rif' => 'J-99999999-1',
            'fiscal_address' => 'Nueva dirección fiscal SENIAT',
            'phone' => '0414-1234567',
            'taxpayer_type' => 'formal',
            'description' => 'Actualizada',
        ]);

    $response->assertRedirect(route('companies.show', ['company' => $company->id, 'id' => $company->id]));

    $updated = $company->fresh();
    expect($updated->name)->toBe('Empresa Actualizada')
        ->and($updated->rif)->toBe('J-99999999-1')
        ->and($updated->fiscal_address)->toBe('Nueva dirección fiscal SENIAT')
        ->and($updated->phone)->toBe('0414-1234567')
        ->and($updated->taxpayer_type)->toBe('formal');
});

test('invalid taxpayer_type is rejected by validation', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('companies.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid(),
            'name' => 'Empresa Invalida',
            'taxpayer_type' => 'invalid_type',
        ]);

    $response->assertSessionHasErrors(['taxpayer_type']);
});
