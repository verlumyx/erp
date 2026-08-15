<?php

declare(strict_types=1);

use App\Modules\Account\Models\Account;
use App\Modules\Service\Models\Service;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\actingAs;

test('an authorized user gets the decrypted credentials', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);
    $account = Account::factory()->forService($service)->create([
        'email' => 'creds@test.com',
        'password_encrypted' => 'the-real-password',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('accounts.credentials', ['company' => $company->id, 'id' => $account->id]));

    $response->assertOk();
    $response->assertExactJson([
        'email' => 'creds@test.com',
        'password' => 'the-real-password',
    ]);
});

test('each credentials access is logged', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);
    $account = Account::factory()->forService($service)->create();

    Log::shouldReceive('info')
        ->once()
        ->withArgs(fn (string $message, array $context = []): bool => $message === 'account.credentials.accessed'
            && ($context['account_id'] ?? null) === $account->id);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('accounts.credentials', ['company' => $company->id, 'id' => $account->id]))
        ->assertOk();
});

test('a user without the credentials permission is forbidden', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['accounts.list', 'accounts.show']);

    $service = Service::factory()->create(['company_id' => $company->id]);
    $account = Account::factory()->forService($service)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('accounts.credentials', ['company' => $company->id, 'id' => $account->id]));

    $response->assertForbidden();
});
