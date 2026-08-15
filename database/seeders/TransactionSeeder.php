<?php

namespace Database\Seeders;

use App\Modules\Account\Models\Account;
use App\Modules\Company\Models\Company;
use App\Modules\Service\Models\Service;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Crea ~28 transacciones variadas (income y expense de distintas categorías
     * y fechas de los últimos 3 meses) para una empresa.
     */
    public function run(): void
    {
        $user = User::query()->first() ?? User::factory()->create();

        $company = Company::query()->first() ?? Company::create([
            'name' => 'Empresa Demo',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $service = Service::query()->where('company_id', $company->id)->first()
            ?? Service::factory()->create(['company_id' => $company->id]);

        $accounts = Account::query()->where('company_id', $company->id)->take(2)->get();

        if ($accounts->count() < 2) {
            $accounts = Account::factory()
                ->count(2)
                ->create([
                    'company_id' => $company->id,
                    'service_id' => $service->id,
                ]);
        }

        $context = ['company_id' => $company->id, 'recorded_by' => $user->id];

        // 12 ingresos variados.
        Transaction::factory()->count(12)->income()->create($context);

        // 12 gastos generales variados.
        Transaction::factory()->count(12)->expense()->create($context);

        // 4 pagos de cuentas de streaming (gasto polimórfico ligado a Account).
        foreach ($accounts as $account) {
            Transaction::factory()
                ->count(2)
                ->forAccount($account)
                ->create(['recorded_by' => $user->id]);
        }
    }
}
