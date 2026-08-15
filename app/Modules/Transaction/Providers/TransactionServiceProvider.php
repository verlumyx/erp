<?php

declare(strict_types=1);

namespace App\Modules\Transaction\Providers;

use App\Modules\Account\Models\Account;
use App\Modules\ManualTransaction\Models\ManualTransaction;
use App\Modules\Refund\Models\Refund;
use App\Modules\Sale\Models\Sale;
use App\Modules\Transaction\Repositories\Contracts\TransactionRepositoryInterface;
use App\Modules\Transaction\Repositories\TransactionRepository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class TransactionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TransactionRepositoryInterface::class,
            TransactionRepository::class,
        );
    }

    public function boot(): void
    {
        // Mapa polimórfico de `related`. Se almacenan alias cortos en
        // `related_type` (p. ej. 'Account', 'Sale').
        Relation::morphMap([
            'Account' => Account::class,
            'Sale' => Sale::class,
            'Refund' => Refund::class,
            'ManualTransaction' => ManualTransaction::class,
        ]);
    }
}
