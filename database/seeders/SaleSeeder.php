<?php

namespace Database\Seeders;

use App\Modules\Account\Models\Account;
use App\Modules\Account\Models\Profile;
use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\Plan\Models\Plan;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleProfile;
use App\Modules\Sale\Models\SaleRenewal;
use App\Modules\Service\Models\Service;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SaleSeeder extends Seeder
{
    private const MAX_PROFILES = 4;

    /**
     * Crea 18 ventas variadas (activas, en gracia, expiradas fuera de gracia,
     * canceladas y de cuenta completa), con sus sale_profiles, renovaciones y
     * transacciones de ingreso asociadas, para una empresa demo.
     */
    public function run(): void
    {
        $user = User::query()->first() ?? User::factory()->create();

        $company = Company::query()->first() ?? Company::create([
            'name' => 'Empresa Demo',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $service = Service::factory()->create([
            'company_id' => $company->id,
            'max_profiles' => self::MAX_PROFILES,
        ]);

        $profilePlan = Plan::factory()->create([
            'company_id' => $company->id,
            'service_id' => $service->id,
            'capacity' => 'profile',
            'duration_days' => 30,
        ]);

        $fullPlan = Plan::factory()->create([
            'company_id' => $company->id,
            'service_id' => $service->id,
            'capacity' => 'full_account',
            'duration_days' => 30,
        ]);

        // Pool de cuentas con sus 4 profiles disponibles cada una.
        $accounts = Account::factory()
            ->count(12)
            ->create(['company_id' => $company->id, 'service_id' => $service->id])
            ->map(function (Account $account): array {
                $profiles = collect(range(1, self::MAX_PROFILES))->map(fn (int $number) => Profile::factory()->create([
                    'account_id' => $account->id,
                    'number' => $number,
                    'status' => 'available',
                ]));

                return ['account' => $account, 'profiles' => $profiles->values()];
            });

        $clients = Client::factory()->count(6)->create(['company_id' => $company->id]);
        Client::factory()->inactive()->create(['company_id' => $company->id]);

        $accountCursor = 0;

        // Toma un profile libre del pool (capacity profile).
        $takeProfile = function () use ($accounts, &$accountCursor): Profile {
            for ($i = 0; $i < $accounts->count(); $i++) {
                $entry = $accounts[($accountCursor + $i) % $accounts->count()];
                $free = $entry['profiles']->firstWhere('status', 'available');

                if ($free !== null) {
                    $accountCursor = ($accountCursor + $i + 1) % $accounts->count();

                    return $free;
                }
            }

            throw new \RuntimeException('No hay profiles disponibles en el seeder.');
        };

        // Toma una cuenta entera libre (capacity full_account).
        $takeAccount = function () use ($accounts): array {
            foreach ($accounts as $entry) {
                if ($entry['profiles']->every(fn (Profile $p): bool => $p->status === 'available')) {
                    return $entry['profiles']->all();
                }
            }

            throw new \RuntimeException('No hay cuentas completas disponibles en el seeder.');
        };

        // Primero las ventas de cuenta completa, mientras hay cuentas enteras libres.
        for ($i = 0; $i < 3; $i++) {
            $accountProfiles = $takeAccount();

            $sale = Sale::factory()->active()->fullAccount()->forPlan($fullPlan)->create([
                'company_id' => $company->id,
                'client_id' => $clients->random()->id,
                'agent_id' => $user->id,
            ]);

            $this->attachProfiles($sale, $accountProfiles, true);
            $this->recordSaleTransaction($sale, $user->id, $service->name);
        }

        $states = [
            ...array_fill(0, 7, 'active'),
            ...array_fill(0, 3, 'expiredInGrace'),
            ...array_fill(0, 2, 'expiredOutOfGrace'),
            ...array_fill(0, 3, 'cancelled'),
        ];

        foreach ($states as $index => $state) {
            $profile = $takeProfile();
            $occupy = in_array($state, ['active', 'expiredInGrace'], true);

            $sale = Sale::factory()->{$state}()->forPlan($profilePlan)->create([
                'company_id' => $company->id,
                'client_id' => $clients->random()->id,
                'agent_id' => $user->id,
            ]);

            $this->attachProfiles($sale, [$profile], $occupy);
            $this->recordSaleTransaction($sale, $user->id, $service->name);

            // Algunas activas con historial de renovaciones.
            if ($state === 'active' && $index % 2 === 0) {
                $this->addRenewals($sale, $user->id, 2);
            }
        }
    }

    /**
     * @param  array<int, Profile>  $profiles
     */
    private function attachProfiles(Sale $sale, array $profiles, bool $occupy): void
    {
        foreach ($profiles as $profile) {
            SaleProfile::create([
                'id' => Str::uuid7()->toString(),
                'sale_id' => $sale->id,
                'profile_id' => $profile->id,
            ]);

            if ($occupy) {
                $profile->update(['status' => 'occupied']);
            }
        }
    }

    private function addRenewals(Sale $sale, string $userId, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $renewal = SaleRenewal::factory()->forSale($sale)->create([
                'renewed_by' => $userId,
                'duration_days' => $sale->duration_days,
                'price' => $sale->price,
            ]);

            Transaction::create([
                'id' => Str::uuid7()->toString(),
                'company_id' => $sale->company_id,
                'type' => Transaction::TYPE_INCOME,
                'category' => 'renewal',
                'amount' => $renewal->price,
                'currency' => 'USD',
                'date' => $renewal->renewed_at->toDateString(),
                'payment_method' => 'cash',
                'description' => "Renovación de venta {$sale->code}",
                'recorded_by' => $userId,
                'related_type' => 'Sale',
                'related_id' => $sale->id,
            ]);
        }
    }

    private function recordSaleTransaction(Sale $sale, string $userId, string $serviceName): void
    {
        Transaction::create([
            'id' => Str::uuid7()->toString(),
            'company_id' => $sale->company_id,
            'type' => Transaction::TYPE_INCOME,
            'category' => 'sale',
            'amount' => $sale->price,
            'currency' => 'USD',
            'date' => $sale->start_date->toDateString(),
            'payment_method' => 'cash',
            'description' => "Venta {$serviceName}",
            'recorded_by' => $userId,
            'related_type' => 'Sale',
            'related_id' => $sale->id,
        ]);
    }
}
