<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Providers;

use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use App\Modules\InventoryMovement\Repositories\InventoryMovementRepository;
use Illuminate\Support\ServiceProvider;

class InventoryMovementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            InventoryMovementRepositoryInterface::class,
            InventoryMovementRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }
}
