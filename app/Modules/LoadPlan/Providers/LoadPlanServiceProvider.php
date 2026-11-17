<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Providers;

use App\Modules\LoadPlan\Repositories\Contracts\LoadPlanRepositoryInterface;
use App\Modules\LoadPlan\Repositories\LoadPlanRepository;
use Illuminate\Support\ServiceProvider;

class LoadPlanServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            LoadPlanRepositoryInterface::class,
            LoadPlanRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
