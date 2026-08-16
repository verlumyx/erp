<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Providers;

use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use App\Modules\WarehouseLocation\Repositories\WarehouseLocationRepository;
use Illuminate\Support\ServiceProvider;

class WarehouseLocationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            WarehouseLocationRepositoryInterface::class,
            WarehouseLocationRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
