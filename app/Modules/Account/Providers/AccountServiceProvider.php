<?php

declare(strict_types=1);

namespace App\Modules\Account\Providers;

use App\Modules\Account\Models\Account;
use App\Modules\Account\Policies\AccountPolicy;
use App\Modules\Account\Repositories\AccountRepository;
use App\Modules\Account\Repositories\Contracts\AccountRepositoryInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AccountServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AccountRepositoryInterface::class,
            AccountRepository::class,
        );
    }

    public function boot(): void
    {
        Gate::policy(Account::class, AccountPolicy::class);

        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
