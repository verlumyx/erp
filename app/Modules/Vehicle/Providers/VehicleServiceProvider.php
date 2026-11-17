<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Providers;

use App\Modules\Vehicle\Repositories\Contracts\VehicleRepositoryInterface;
use App\Modules\Vehicle\Repositories\VehicleRepository;
use Illuminate\Support\ServiceProvider;

class VehicleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            VehicleRepositoryInterface::class,
            VehicleRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
