<?php

declare(strict_types=1);

namespace App\Modules\Plan\Providers;

use App\Modules\Plan\Repositories\Contracts\PlanRepositoryInterface;
use App\Modules\Plan\Repositories\PlanRepository;
use Illuminate\Support\ServiceProvider;

class PlanServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PlanRepositoryInterface::class,
            PlanRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
