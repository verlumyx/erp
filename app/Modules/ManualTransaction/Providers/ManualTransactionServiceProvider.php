<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Providers;

use App\Modules\ManualTransaction\Models\ManualTransaction;
use App\Modules\ManualTransaction\Policies\ManualTransactionPolicy;
use App\Modules\ManualTransaction\Repositories\Contracts\ManualTransactionRepositoryInterface;
use App\Modules\ManualTransaction\Repositories\ManualTransactionRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ManualTransactionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ManualTransactionRepositoryInterface::class,
            ManualTransactionRepository::class,
        );
    }

    public function boot(): void
    {
        Gate::policy(ManualTransaction::class, ManualTransactionPolicy::class);

        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
