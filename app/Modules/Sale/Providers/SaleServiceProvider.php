<?php

declare(strict_types=1);

namespace App\Modules\Sale\Providers;

use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Policies\SalePolicy;
use App\Modules\Sale\Repositories\Contracts\SaleRepositoryInterface;
use App\Modules\Sale\Repositories\SaleRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class SaleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SaleRepositoryInterface::class,
            SaleRepository::class,
        );
    }

    public function boot(): void
    {
        Gate::policy(Sale::class, SalePolicy::class);

        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
